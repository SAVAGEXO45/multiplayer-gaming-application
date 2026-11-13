@props(['slug', 'class' => 'w-full h-48'])

@php
    $artworkMap = [
        'cyber-strike-fps' => [
            'image' => '/images/ref/shooter.jpg',
            'alt' => 'Cyber Strike: Tactical Ops FPS Cover Art',
            'overlay' => 'from-[#090C14] via-[#090C14]/25 to-transparent',
            'accent' => 'border-orange-500/40',
        ],
        'nitro-apex-gt' => [
            'image' => '/images/ref/racing.jpg',
            'alt' => 'Nitro Apex GT: Street Horizon Supercar Cover Art',
            'overlay' => 'from-[#090C14] via-[#090C14]/25 to-transparent',
            'accent' => 'border-sky-500/40',
        ],
        'shadow-dojo-brawl' => [
            'image' => '/images/ref/hero-warrior-hd.jpg',
            'alt' => 'Shadow Dojo: Iron Fist Martial Arts Cover Art',
            'overlay' => 'from-[#090C14] via-[#090C14]/25 to-transparent',
            'accent' => 'border-rose-500/40',
        ],
        'emberfall-survivors' => [
            'image' => '/images/ref/sandbox.jpg',
            'alt' => 'Emberfall: Blade & Ruin Adventure Cover Art',
            'overlay' => 'from-[#090C14] via-[#090C14]/25 to-transparent',
            'accent' => 'border-amber-500/40',
        ],
        'deadzone-outbreak' => [
            'image' => '/images/games/hero-arena.jpg',
            'alt' => 'Deadzone: Outbreak 2099 Zombie Survival Cover Art',
            'overlay' => 'from-[#090C14] via-[#090C14]/30 to-transparent',
            'accent' => 'border-emerald-500/40',
        ],
        'street-hoops-pro' => [
            'image' => '/images/ref/action.jpg',
            'alt' => 'Street Hoops 3x3: Pro Court Sports Cover Art',
            'overlay' => 'from-[#090C14] via-[#090C14]/25 to-transparent',
            'accent' => 'border-orange-400/40',
        ],
        'moto-xtreme-trials' => [
            'image' => '/images/ref/controller-hd.jpg',
            'alt' => 'Moto Xtreme: Gravity Trials Stunt Bike Cover Art',
            'overlay' => 'from-[#090C14] via-[#090C14]/25 to-transparent',
            'accent' => 'border-purple-500/40',
        ],
        'nova-vanguard-3d' => [
            'image' => '/images/ref/setup-hd.jpg',
            'alt' => 'Nova Vanguard: Starfighter Assault Space Cover Art',
            'overlay' => 'from-[#090C14] via-[#090C14]/25 to-transparent',
            'accent' => 'border-cyan-500/40',
        ],
        'arena-tag-royale' => [
            'image' => '/images/ref/mascot-logo.jpg',
            'alt' => 'Cyber Tag: Crown Royale Multiplayer Cover Art',
            'overlay' => 'from-[#090C14] via-[#090C14]/25 to-transparent',
            'accent' => 'border-violet-500/40',
        ],
        'prism-breaker-overdrive' => [
            'image' => '/images/ref/treasure.jpg',
            'alt' => 'Prism Breaker: Neon Overdrive Arcade Cover Art',
            'overlay' => 'from-[#090C14] via-[#090C14]/25 to-transparent',
            'accent' => 'border-pink-500/40',
        ],
    ];

    $art = $artworkMap[$slug] ?? $artworkMap['cyber-strike-fps'];
@endphp

<div {{ $attributes->merge(['class' => "relative overflow-hidden bg-[#090C14] select-none {$class}"]) }}>
    <img src="{{ $art['image'] }}"
         alt="{{ $art['alt'] }}"
         loading="lazy"
         class="w-full h-full object-cover object-center transition-transform duration-500 ease-out group-hover:scale-105" />
    <div class="pointer-events-none absolute inset-0 bg-gradient-to-t {{ $art['overlay'] }}"></div>
</div>
