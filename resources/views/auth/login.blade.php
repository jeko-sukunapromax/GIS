<x-guest-layout>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Outfit:wght@400;500;600;700;800;900&display=swap');

        /* Exact styling matching the uploaded screen format */
        .gis-login-body {
            font-family: 'Inter', sans-serif;
            background-color: #030712;
        }

        .gis-title-font {
            font-family: 'Outfit', sans-serif;
        }

        /* Show/Hide password toggle styles */
        .password-container {
            position: relative;
        }

        .password-toggle-btn {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: color 0.2s;
        }

        .password-toggle-btn:hover {
            color: rgba(255, 255, 255, 0.9);
        }

        /* Custom glow effect for the primary yellow button */
        .yellow-glow-btn {
            background-color: #facc15;
            color: #030712;
            box-shadow: 0 0 20px rgba(250, 204, 21, 0.4);
            transition: all 0.2s ease-in-out;
        }

        .yellow-glow-btn:hover {
            background-color: #eab308;
            box-shadow: 0 0 25px rgba(250, 204, 21, 0.6);
            transform: translateY(-1px);
        }

        /* Ambient subtle slide-in animation */
        @keyframes slide-in-up {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-slide {
            animation: slide-in-up 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
    </style>

    <main class="gis-login-body relative flex min-h-screen w-full items-center justify-center overflow-hidden">
        <!-- Topographic Map Background Image with Deep Midnight-Blue Overlay Tint -->
        <div class="absolute inset-0 z-0 h-full w-full">
            <img src="/images/gis_bg.png" class="h-full w-full object-cover select-none pointer-events-none" alt="Topographic GIS Map">
            <!-- Midnight blue transparent overlay matching target's dark tint styling -->
            <div class="absolute inset-0 bg-[#070b19]/85 backdrop-blur-[1px]"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-[#030712] via-transparent to-[#030712]/40"></div>
        </div>

        <!-- Top Accent glow bar -->
        <div class="absolute inset-x-0 top-0 z-20 h-[3px] bg-gradient-to-r from-transparent via-yellow-400/50 to-transparent"></div>

        <!-- Content Area -->
        <div class="relative z-10 mx-auto w-full max-w-7xl lg:max-w-[1360px] px-6 py-12 lg:px-10">
            <div class="grid items-center gap-12 lg:grid-cols-2">
                
                <!-- Left Column: Large Official Bayambang Seal (Pushed to the left on large screens) -->
                <div class="animate-slide flex justify-center lg:justify-start items-center" style="animation-delay: 100ms;">
                    <div class="relative">
                        <!-- Subtle ambient backglow for the seal -->
                        <div class="absolute -inset-4 rounded-full bg-blue-500/10 blur-2xl"></div>
                        <img src="/images/logo.png" alt="Bayan ng Bayambang Seal" 
                            class="relative w-64 h-64 md:w-80 md:h-80 lg:w-[360px] lg:h-[360px] object-contain drop-shadow-[0_4px_24px_rgba(0,153,255,0.25)] select-none">
                    </div>
                </div>

                <!-- Right Column: Brand titles and Glassmorphic Input Box (Pushed to the right on large screens) -->
                <div class="animate-slide w-full flex justify-center lg:justify-end" style="animation-delay: 200ms;">
                    <div class="w-full max-w-lg flex flex-col items-center lg:items-start justify-center">
                        
                        <!-- Brand title GeoBayambang -->
                        <div class="mb-7 text-center lg:text-left w-full">
                            <h1 class="gis-title-font text-4xl md:text-5xl lg:text-6xl font-black tracking-tight text-white drop-shadow">
                                Geo<span class="text-yellow-400">Bayambang</span>
                            </h1>
                            <p class="text-slate-400 text-xs md:text-sm tracking-[0.15em] uppercase font-semibold mt-1">
                                Geographic Information System
                            </p>
                        </div>

                        <!-- Glassmorphic Login Form Container -->
                        <div class="w-full rounded-[28px] border border-white/15 bg-white/[0.03] p-8 md:p-10 shadow-2xl shadow-black/40 backdrop-blur-md">


                        <!-- Laravel Validation Errors -->
                        <x-validation-errors class="mb-5 rounded-xl border border-red-500/20 bg-red-500/[0.03] p-4 text-xs text-red-300" />

                        <!-- Laravel Session Status -->
                        @session('status')
                            <div class="mb-5 rounded-xl border border-emerald-500/20 bg-emerald-500/[0.03] p-4 text-xs font-semibold text-emerald-300">
                                {{ $value }}
                            </div>
                        @endsession

                        <!-- Form action directed to route('login') -->
                        <form method="POST" action="{{ route('login') }}" class="space-y-5">
                            @csrf

                            <!-- Email Field -->
                            <div>
                                <label for="email" class="mb-2 block text-sm font-semibold text-white">Email</label>
                                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                                    class="block w-full rounded-xl border border-white/30 bg-white/[0.08] px-4 py-3.5 text-sm text-white placeholder-white/40 outline-none transition duration-200 focus:border-white/60 focus:bg-white/[0.12]"
                                    placeholder="your@email.com">
                            </div>

                            <!-- Password Field with visibility toggle -->
                            <div>
                                <label for="password" class="mb-2 block text-sm font-semibold text-white">Password</label>
                                <div class="password-container">
                                    <input id="password" name="password" type="password" required autocomplete="current-password"
                                        class="block w-full rounded-xl border border-white/30 bg-white/[0.08] pl-4 pr-12 py-3.5 text-sm text-white placeholder-white/40 outline-none transition duration-200 focus:border-white/60 focus:bg-white/[0.12]"
                                        placeholder="••••••••">
                                    
                                    <!-- Simple Native show/hide password toggle button -->
                                    <button type="button" onclick="togglePasswordVisibility()" class="password-toggle-btn" title="Toggle password visibility">
                                        <svg id="eye-icon" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Remember me checkbox -->
                            <div class="flex items-center">
                                <label for="remember_me" class="inline-flex items-center gap-2.5 text-xs text-white/90 cursor-pointer select-none">
                                    <input id="remember_me" name="remember" type="checkbox"
                                        class="h-4 w-4 rounded border-white/30 bg-white/[0.08] text-yellow-400 focus:ring-yellow-400/20 focus:ring-offset-0">
                                    <span>Remember me</span>
                                </label>
                            </div>

                            <!-- Bottom row: Forgot Password & Log In Button -->
                            <div class="flex items-center justify-between pt-2">
                                <div>
                                    @if (Route::has('password.request'))
                                        <a href="{{ route('password.request') }}" class="text-xs text-white/90 hover:text-white underline transition duration-200">
                                            Forgot Password?
                                        </a>
                                    @endif
                                </div>

                                <button type="submit" class="yellow-glow-btn rounded-xl px-7 py-2.5 text-xs font-bold uppercase tracking-wider">
                                    LOG IN
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Native JavaScript for show/hide password toggle -->
    <script>
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                // Toggle eye icon to eye-off state
                eyeIcon.innerHTML = `
                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                    <line x1="1" y1="1" x2="23" y2="23"/>
                `;
            } else {
                passwordInput.type = 'password';
                // Toggle eye icon back to original state
                eyeIcon.innerHTML = `
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                    <circle cx="12" cy="12" r="3"/>
                `;
            }
        }
    </script>
</x-guest-layout>
