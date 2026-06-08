<?php

namespace App\Services;

use App\Models\MapLayerType;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LayerMetadataSchema
{
    private const FIELD_TYPES = ['text', 'number', 'select', 'textarea', 'boolean', 'date'];

    private const DEFAULT_SCHEMAS = [
        'barangay_hall' => [
            ['key' => 'official', 'label' => 'Brgy. Captain / Official Name', 'type' => 'text', 'placeholder' => 'e.g. Capt. Juan Ramos'],
            ['key' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['Operational', 'Under Maintenance']],
            ['key' => 'contact', 'label' => 'Contact No.', 'type' => 'text', 'placeholder' => '09XX-XXX-XXXX'],
        ],
        'health_center' => [
            ['key' => 'nurse', 'label' => 'Nurse / Midwife Name', 'type' => 'text', 'placeholder' => 'e.g. Maria Santos, RN'],
            ['key' => 'hours', 'label' => 'Operating Hours', 'type' => 'text', 'placeholder' => '8:00 AM - 5:00 PM'],
            ['key' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['Operational', 'Closed']],
        ],
        'multipurpose_bldg' => [
            ['key' => 'capacity', 'label' => 'Holding Capacity', 'type' => 'text', 'placeholder' => 'e.g. 200 persons'],
            ['key' => 'evac_ready', 'label' => 'Evacuation Ready?', 'type' => 'select', 'options' => ['Yes', 'No']],
        ],
        'covered_court' => [
            ['key' => 'capacity', 'label' => 'Holding Capacity', 'type' => 'text', 'placeholder' => 'e.g. 200 persons'],
            ['key' => 'evac_ready', 'label' => 'Evacuation Ready?', 'type' => 'select', 'options' => ['Yes', 'No']],
        ],
        'police_post' => [
            ['key' => 'on_duty', 'label' => 'Active Officers on Duty', 'type' => 'text', 'placeholder' => 'e.g. 2 officers'],
            ['key' => 'contact', 'label' => 'Emergency Contact', 'type' => 'text', 'placeholder' => 'e.g. Hotline 911'],
        ],
        'evac_center' => [
            ['key' => 'capacity', 'label' => 'Holding Capacity', 'type' => 'text', 'placeholder' => 'e.g. 200 persons'],
            ['key' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['Operational', 'Under Maintenance', 'Full', 'Closed']],
            ['key' => 'contact', 'label' => 'Contact No.', 'type' => 'text', 'placeholder' => '09XX-XXX-XXXX'],
        ],
        'bert_member' => [
            ['key' => 'role', 'label' => 'Responder Role', 'type' => 'text', 'placeholder' => 'e.g. Team Leader, First Aider'],
            ['key' => 'skills', 'label' => 'Special Skills', 'type' => 'text', 'placeholder' => 'e.g. Flood Rescue, First Aid, CPR'],
            ['key' => 'phone', 'label' => 'Contact Number', 'type' => 'text', 'placeholder' => '09XX-XXX-XXXX'],
            ['key' => 'status', 'label' => 'Deployment Status', 'type' => 'select', 'options' => ['Active', 'Inactive']],
        ],
        'road_network' => [
            ['key' => 'type', 'label' => 'Road Type', 'type' => 'text', 'placeholder' => 'e.g. Concrete Highway'],
            ['key' => 'status', 'label' => 'Condition', 'type' => 'select', 'options' => ['Good Condition', 'Needs Maintenance', 'Damaged / Closed']],
            ['key' => 'width', 'label' => 'Average Width', 'type' => 'text', 'placeholder' => 'e.g. 6.0 meters'],
            ['key' => 'length', 'label' => 'Length', 'type' => 'text', 'placeholder' => 'e.g. 1.2 km'],
        ],
        'population_density' => [
            ['key' => 'density_level', 'label' => 'Density Level', 'type' => 'select', 'options' => ['High', 'Medium', 'Low']],
            ['key' => 'est_households', 'label' => 'Est. Households', 'type' => 'number', 'placeholder' => 'e.g. 150'],
        ],
        'household_distribution' => [
            ['key' => 'house_no', 'label' => 'Household No.', 'type' => 'text', 'placeholder' => 'e.g. 104'],
            ['key' => 'head', 'label' => 'Household Head', 'type' => 'text', 'placeholder' => 'e.g. Juan Dela Cruz'],
            ['key' => 'members', 'label' => 'Family Members Count', 'type' => 'number', 'placeholder' => 'e.g. 5'],
            ['key' => 'hazard_risk', 'label' => 'Flood/Landslide Risk', 'type' => 'select', 'options' => ['Low', 'Moderate', 'High']],
        ],
    ];

    public function defaultFor(string $code, string $geomType = 'point'): array
    {
        return $this->normalizeSchema(self::DEFAULT_SCHEMAS[$code] ?? $this->genericSchema($geomType));
    }

    public function schemaFor(MapLayerType $layerType): array
    {
        $schema = $layerType->metadata_schema ?: $this->defaultFor($layerType->code, $layerType->geom_type);

        return $this->normalizeSchema($schema);
    }

    public function fromJson(?string $json, string $code, string $geomType): array
    {
        if ($json === null || trim($json) === '') {
            return $this->defaultFor($code, $geomType);
        }

        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            throw ValidationException::withMessages([
                'metadata_schema_json' => 'Metadata schema must be valid JSON.',
            ]);
        }

        return $this->normalizeSchema($decoded);
    }

    public function normalizeSchema(array $schema): array
    {
        $fields = array_is_list($schema) ? $schema : ($schema['fields'] ?? []);

        return collect($fields)
            ->filter(fn ($field) => is_array($field) && filled($field['key'] ?? null))
            ->map(fn (array $field) => $this->normalizeField($field))
            ->unique('key')
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    public function normalizeMetadata(MapLayerType $layerType, array $metadata): array
    {
        $schema = $this->schemaFor($layerType);
        $normalized = [];
        $errors = [];

        foreach ($schema as $field) {
            $key = $field['key'];
            $value = $metadata[$key] ?? null;
            $isBlank = $value === null || $value === '';

            if (($field['required'] ?? false) && $isBlank) {
                $errors["metadata.{$key}"] = "{$field['label']} is required.";
                continue;
            }

            if ($isBlank) {
                continue;
            }

            $normalized[$key] = $this->normalizeValue($field, $value);
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $normalized;
    }

    private function normalizeField(array $field): array
    {
        $key = (string) Str::of((string) $field['key'])->trim()->snake();
        $type = in_array($field['type'] ?? 'text', self::FIELD_TYPES, true) ? $field['type'] : 'text';
        $options = collect($field['options'] ?? [])
            ->filter(fn ($option) => $option !== null && $option !== '')
            ->map(fn ($option) => trim((string) $option))
            ->values()
            ->all();

        return array_filter([
            'key' => $key,
            'label' => trim((string) ($field['label'] ?? Str::headline($key))),
            'type' => $type,
            'required' => (bool) ($field['required'] ?? false),
            'options' => $type === 'select' ? $options : null,
            'placeholder' => isset($field['placeholder']) ? trim((string) $field['placeholder']) : null,
        ], fn ($value) => $value !== null && $value !== []);
    }

    private function normalizeValue(array $field, mixed $value): mixed
    {
        return match ($field['type']) {
            'number' => is_numeric($value) ? $value + 0 : trim((string) $value),
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            default => trim((string) $value),
        };
    }

    private function genericSchema(string $geomType): array
    {
        $label = match ($geomType) {
            'polyline' => 'Line Details',
            'polygon' => 'Area Details',
            default => 'Asset Details',
        };

        return [
            ['key' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['Operational', 'Needs Maintenance', 'Inactive']],
            ['key' => 'description', 'label' => $label, 'type' => 'textarea', 'placeholder' => 'Notes, condition, capacity, or other official details'],
        ];
    }
}
