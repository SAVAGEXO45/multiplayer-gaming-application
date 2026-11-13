/**
 * NEXUS ARENA — Real-Time HTML5 Canvas + Web Audio Action Game Suite
 * Supports all 10 playable action games with 60fps physics, particles,
 * synthesized Web Audio SFX + synthwave BGM, keyboard + mouse + mobile touch controls,
 * pause/restart/fullscreen, and live backend score & leaderboard synchronization.
 */

class NexusSoundEngine {
    constructor() {
        this.ctx = null;
        this.muted = false;
        this.bgmInterval = null;
        this.bgmStep = 0;
    }

    init() {
        if (!this.ctx) {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (AudioCtx) {
                this.ctx = new AudioCtx();
            }
        }
        if (this.ctx && this.ctx.state === 'suspended') {
            this.ctx.resume().catch(() => {});
        }
    }

    toggleMute() {
        this.muted = !this.muted;
        if (this.muted) {
            this.stopBGM();
        }
        return this.muted;
    }

    playTone(freq, type = 'sawtooth', duration = 0.12, vol = 0.12, slideTo = null) {
        if (this.muted) return;
        this.init();
        if (!this.ctx) return;

        try {
            const now = this.ctx.currentTime;
            const osc = this.ctx.createOscillator();
            const gain = this.ctx.createGain();
            osc.type = type;
            osc.frequency.setValueAtTime(freq, now);
            if (slideTo) {
                osc.frequency.exponentialRampToValueAtTime(Math.max(20, slideTo), now + duration);
            }
            gain.gain.setValueAtTime(vol, now);
            gain.gain.exponentialRampToValueAtTime(0.001, now + duration);
            osc.connect(gain);
            gain.connect(this.ctx.destination);
            osc.start(now);
            osc.stop(now + duration);
        } catch (e) {}
    }

    playNoise(duration = 0.2, vol = 0.18) {
        if (this.muted) return;
        this.init();
        if (!this.ctx) return;

        try {
            const bufferSize = Math.floor(this.ctx.sampleRate * duration);
            const buffer = this.ctx.createBuffer(1, bufferSize, this.ctx.sampleRate);
            const data = buffer.getChannelData(0);
            for (let i = 0; i < bufferSize; i++) {
                data[i] = Math.random() * 2 - 1;
            }
            const noise = this.ctx.createBufferSource();
            noise.buffer = buffer;

            const filter = this.ctx.createBiquadFilter();
            filter.type = 'lowpass';
            filter.frequency.setValueAtTime(900, this.ctx.currentTime);

            const gain = this.ctx.createGain();
            gain.gain.setValueAtTime(vol, this.ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, this.ctx.currentTime + duration);

            noise.connect(filter);
            filter.connect(gain);
            gain.connect(this.ctx.destination);
            noise.start();
        } catch (e) {}
    }

    shoot() {
        this.playTone(580, 'sawtooth', 0.09, 0.11, 140);
    }

    heavyShoot() {
        this.playNoise(0.18, 0.22);
        this.playTone(220, 'square', 0.16, 0.14, 55);
    }

    hit() {
        this.playTone(180, 'triangle', 0.08, 0.15, 70);
    }

    explosion() {
        this.playNoise(0.38, 0.26);
        this.playTone(110, 'sawtooth', 0.35, 0.18, 28);
    }

    coin() {
        this.playTone(660, 'sine', 0.08, 0.12, 990);
        setTimeout(() => this.playTone(990, 'sine', 0.11, 0.12, 1320), 60);
    }

    powerup() {
        const notes = [440, 554, 659, 880];
        notes.forEach((n, idx) => {
            setTimeout(() => this.playTone(n, 'triangle', 0.1, 0.12), idx * 55);
        });
    }

    jump() {
        this.playTone(240, 'sine', 0.15, 0.11, 560);
    }

    startBGM() {
        if (this.muted || this.bgmInterval) return;
        const bassline = [110, 110, 130.8, 146.8, 110, 110, 164.8, 146.8];
        this.bgmInterval = setInterval(() => {
            if (this.muted) return;
            const note = bassline[this.bgmStep % bassline.length];
            this.playTone(note, 'triangle', 0.16, 0.035);
            this.bgmStep++;
        }, 240);
    }

    stopBGM() {
        if (this.bgmInterval) {
            clearInterval(this.bgmInterval);
            this.bgmInterval = null;
        }
    }
}

export class NexusArcadeInstance {
    constructor(config) {
        this.canvas = document.getElementById(config.canvasId);
        if (!this.canvas) return;
        this.ctx = this.canvas.getContext('2d');
        this.slug = config.gameSlug || 'cyber-strike-fps';
        this.gameName = config.gameName || 'Nexus Action Game';
        this.scoreUrl = config.scoreUrl || null;
        this.roomMoveUrl = config.roomMoveUrl || null;
        this.csrfToken = config.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || '';
        this.onScoreSaved = config.onScoreSaved || null;

        this.width = 960;
        this.height = 540;
        this.canvas.width = this.width;
        this.canvas.height = this.height;

        this.sfx = new NexusSoundEngine();
        this.keys = {};
        this.mouse = { x: this.width / 2, y: this.height / 2, down: false, clicked: false };

        this.state = 'start'; // start | playing | paused | gameover
        this.score = 0;
        this.highScore = Number(config.initialHighScore || localStorage.getItem(`nexus_hi_${this.slug}`) || 0);
        this.level = 1;
        this.health = 100;
        this.maxHealth = 100;
        this.shield = 100;
        this.ammo = 30;
        this.maxAmmo = 30;
        this.combo = 0;
        this.comboTimer = 0;
        this.timeElapsed = 0;
        this.statusBanner = '';
        this.bannerTimer = 0;

        this.particles = [];
        this.floatingTexts = [];
        this.entities = [];
        this.projectiles = [];
        this.enemyProjectiles = [];
        this.pickups = [];
        this.stars = Array.from({ length: 70 }, () => ({
            x: Math.random() * this.width,
            y: Math.random() * this.height,
            z: Math.random() * 2.5 + 0.5,
        }));

        this.modeState = {};
        this.bindEvents();
        this.resetGame(false);
        this.lastFrameTime = performance.now();
        this.loop = this.loop.bind(this);
        requestAnimationFrame(this.loop);
    }

    bindEvents() {
        window.addEventListener('keydown', (e) => {
            const code = e.code;
            if (['Space', 'ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].includes(code) && this.state === 'playing') {
                e.preventDefault();
            }
            this.keys[code] = true;

            if (code === 'KeyP' || code === 'Escape') {
                if (this.state === 'playing' || this.state === 'paused') {
                    this.togglePause();
                }
            }
            if (code === 'KeyR') {
                if (this.state === 'gameover') {
                    this.startGame();
                } else if (this.state === 'playing') {
                    this.handleReloadOrSpecial();
                }
            }
            if (code === 'Digit1') this.switchWeapon(0);
            if (code === 'Digit2') this.switchWeapon(1);
            if (code === 'Digit3') this.switchWeapon(2);
            if ((code === 'Space' || code === 'KeyJ' || code === 'KeyK' || code === 'KeyL' || code === 'KeyG' || code === 'KeyE') && this.state === 'playing') {
                this.handleActionKey(code);
            }
        });

        window.addEventListener('keyup', (e) => {
            this.keys[e.code] = false;
            if (this.slug === 'street-hoops-pro' && e.code === 'Space' && this.state === 'playing') {
                this.releaseHoopsShot();
            }
        });

        const updatePointer = (clientX, clientY) => {
            const rect = this.canvas.getBoundingClientRect();
            this.mouse.x = ((clientX - rect.left) / rect.width) * this.width;
            this.mouse.y = ((clientY - rect.top) / rect.height) * this.height;
        };

        this.canvas.addEventListener('mousemove', (e) => updatePointer(e.clientX, e.clientY));
        this.canvas.addEventListener('mousedown', (e) => {
            updatePointer(e.clientX, e.clientY);
            this.mouse.down = true;
            this.mouse.clicked = true;
            if (this.state === 'start' || this.state === 'gameover') {
                this.startGame();
            } else if (this.state === 'paused') {
                this.togglePause();
            } else if (this.state === 'playing') {
                this.handlePrimaryFire();
            }
        });
        window.addEventListener('mouseup', () => {
            if (this.slug === 'street-hoops-pro' && this.mouse.down && this.state === 'playing') {
                this.releaseHoopsShot();
            }
            this.mouse.down = false;
        });

        this.canvas.addEventListener('touchstart', (e) => {
            if (e.touches.length > 0) {
                updatePointer(e.touches[0].clientX, e.touches[0].clientY);
                this.mouse.down = true;
                this.mouse.clicked = true;
                if (this.state === 'start' || this.state === 'gameover') {
                    this.startGame();
                } else if (this.state === 'playing') {
                    this.handlePrimaryFire();
                }
            }
        }, { passive: true });

        this.canvas.addEventListener('touchmove', (e) => {
            if (e.touches.length > 0) {
                updatePointer(e.touches[0].clientX, e.touches[0].clientY);
            }
        }, { passive: true });

        this.canvas.addEventListener('touchend', () => {
            if (this.slug === 'street-hoops-pro' && this.mouse.down && this.state === 'playing') {
                this.releaseHoopsShot();
            }
            this.mouse.down = false;
        });
    }

    pressVirtualKey(code, isDown) {
        this.keys[code] = isDown;
        if (isDown && this.state === 'playing') {
            if (code === 'ActionFire') {
                this.handlePrimaryFire();
            } else if (code === 'ActionSpecial') {
                this.handleActionKey('Space');
                this.handleActionKey('KeyG');
                this.handleActionKey('KeyK');
            } else {
                this.handleActionKey(code);
            }
        }
        if (!isDown && code === 'ActionFire' && this.slug === 'street-hoops-pro' && this.state === 'playing') {
            this.releaseHoopsShot();
        }
    }

    startGame() {
        this.sfx.init();
        this.resetGame(true);
        this.state = 'playing';
        this.sfx.startBGM();
        this.emitHudUpdate();
    }

    togglePause() {
        if (this.state === 'playing') {
            this.state = 'paused';
            this.sfx.stopBGM();
        } else if (this.state === 'paused') {
            this.state = 'playing';
            this.sfx.startBGM();
        }
        this.emitHudUpdate();
    }

    toggleMute() {
        return this.sfx.toggleMute();
    }

    toggleFullscreen() {
        const container = this.canvas.closest('.arcade-stage-wrapper') || this.canvas;
        if (!document.fullscreenElement) {
            container.requestFullscreen?.().catch(() => {});
        } else {
            document.exitFullscreen?.().catch(() => {});
        }
    }

    showBanner(text, duration = 2.0) {
        this.statusBanner = text;
        this.bannerTimer = duration;
    }

    addFloatingText(text, x, y, color = '#fbbf24') {
        this.floatingTexts.push({ text, x, y, vy: -45, alpha: 1, color });
    }

    spawnParticles(x, y, color = '#f97316', count = 14, speed = 180) {
        for (let i = 0; i < count; i++) {
            const angle = Math.random() * Math.PI * 2;
            const spd = (0.3 + Math.random() * 0.7) * speed;
            this.particles.push({
                x,
                y,
                vx: Math.cos(angle) * spd,
                vy: Math.sin(angle) * spd,
                radius: Math.random() * 3.5 + 1.5,
                color,
                alpha: 1,
                decay: 1.4 + Math.random() * 1.2,
            });
        }
    }

    addScore(pts, x = null, y = null, label = null) {
        this.combo++;
        this.comboTimer = 3.2;
        const mult = Math.min(5, 1 + Math.floor(this.combo / 5) * 0.5);
        const total = Math.round(pts * mult);
        this.score += total;

        if (this.score > this.highScore) {
            this.highScore = this.score;
            localStorage.setItem(`nexus_hi_${this.slug}`, String(this.highScore));
        }

        if (x !== null && y !== null) {
            this.addFloatingText(label ? `${label} +${total}` : `+${total}`, x, y, mult > 1.5 ? '#38bdf8' : '#fbbf24');
        }

        // Level progression check
        const nextLevelThreshold = this.level * 1200;
        if (this.score >= nextLevelThreshold && this.level < 30) {
            this.level++;
            this.health = Math.min(this.maxHealth, this.health + 25);
            this.shield = Math.min(100, this.shield + 40);
            this.sfx.powerup();
            this.showBanner(`LEVEL ${this.level} UNLOCKED! SPEED & REWARDS UP!`, 2.4);
        }

        this.emitHudUpdate();
    }

    damagePlayer(amount) {
        if (this.shield > 0) {
            const absorbed = Math.min(this.shield, amount * 0.7);
            this.shield = Math.max(0, this.shield - absorbed);
            amount -= absorbed;
        }
        this.health = Math.max(0, this.health - amount);
        this.combo = 0;
        this.sfx.hit();
        this.spawnParticles(this.width / 2, this.height - 60, '#ef4444', 10, 140);

        if (this.health <= 0) {
            this.triggerGameOver();
        }
        this.emitHudUpdate();
    }

    triggerGameOver() {
        if (this.state === 'gameover') return;
        this.state = 'gameover';
        this.sfx.stopBGM();
        this.sfx.explosion();
        this.emitHudUpdate();
        this.submitScoreToBackend(true);
    }

    async submitScoreToBackend(isFinal = true) {
        if (this.score <= 0) return;

        // 1. Submit to solo/platform score endpoint if available
        if (this.scoreUrl) {
            try {
                const res = await fetch(this.scoreUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                    },
                    body: JSON.stringify({
                        score: this.score,
                        level: this.level,
                        victory: this.score >= 800,
                    }),
                });
                if (res.ok) {
                    const payload = await res.json();
                    if (payload?.data?.high_score) {
                        this.highScore = Math.max(this.highScore, payload.data.high_score);
                    }
                    if (typeof this.onScoreSaved === 'function') {
                        this.onScoreSaved(payload);
                    }
                }
            } catch (e) {}
        }

        // 2. If inside a multiplayer room, also sync score to the room session!
        if (this.roomMoveUrl) {
            try {
                await fetch(this.roomMoveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                    },
                    body: JSON.stringify({
                        move: {
                            score: this.score,
                            level: this.level,
                            combos: this.combo,
                            finished: isFinal,
                            event: isFinal ? `Finished Run (${this.score} pts)` : `Wave ${this.level} Sync`,
                        },
                    }),
                });
            } catch (e) {}
        }
    }

    emitHudUpdate() {
        window.dispatchEvent(new CustomEvent('nexus-hud-update', {
            detail: {
                state: this.state,
                score: this.score,
                highScore: this.highScore,
                level: this.level,
                health: Math.round(this.health),
                shield: Math.round(this.shield),
                ammo: this.ammo,
                combo: this.combo,
            },
        }));
    }

    resetGame(keepHighScore = true) {
        this.score = 0;
        this.level = 1;
        this.health = 100;
        this.maxHealth = 100;
        this.shield = 100;
        this.combo = 0;
        this.comboTimer = 0;
        this.timeElapsed = 0;
        this.particles = [];
        this.floatingTexts = [];
        this.entities = [];
        this.projectiles = [];
        this.enemyProjectiles = [];
        this.pickups = [];

        switch (this.slug) {
            case 'cyber-strike-fps':
                this.ammo = 30;
                this.maxAmmo = 30;
                this.modeState = {
                    weaponIndex: 0,
                    weapons: [
                        { name: 'M4-X Pulse Rifle', ammo: 30, dmg: 34, rate: 0.12, spread: 8, color: '#38bdf8' },
                        { name: 'Vortex Shotgun', ammo: 10, dmg: 24, pellets: 5, rate: 0.45, spread: 34, color: '#f97316' },
                        { name: 'Railgun Sniper', ammo: 6, dmg: 110, rate: 0.65, spread: 2, color: '#a855f7' },
                    ],
                    fireCooldown: 0,
                    reloading: 0,
                    spawnTimer: 0.6,
                    recoil: 0,
                };
                break;

            case 'nitro-apex-gt':
                this.ammo = 100; // Nitro %
                this.modeState = {
                    carX: this.width / 2,
                    carY: this.height - 110,
                    speed: 190,
                    maxSpeed: 340,
                    roadOffset: 0,
                    nitroActive: false,
                    checkpointTimer: 25,
                    distance: 0,
                    spawnTimer: 0.5,
                };
                break;

            case 'shadow-dojo-brawl':
                this.ammo = 0; // Rage %
                this.modeState = {
                    groundY: 445,
                    player: { x: 240, y: 445, vx: 0, vy: 0, dir: 1, state: 'idle', stateTimer: 0, blocking: false },
                    rival: { x: 690, y: 445, vx: 0, vy: 0, dir: -1, hp: 100, maxHp: 100, state: 'idle', stateTimer: 0, aiCooldown: 0.5, name: 'Grandmaster Ryu-Jin' },
                    round: 1,
                    wins: 0,
                };
                break;

            case 'emberfall-survivors':
                this.ammo = 100; // Stamina %
                this.modeState = {
                    px: this.width / 2,
                    py: this.height / 2,
                    slashCooldown: 0,
                    slashRadius: 95,
                    slashVisualTimer: 0,
                    dashCooldown: 0,
                    orbAngle: 0,
                    orbCount: 2,
                    spawnTimer: 0.7,
                    xp: 0,
                };
                break;

            case 'deadzone-outbreak':
                this.ammo = 36;
                this.maxAmmo = 36;
                this.modeState = {
                    px: this.width / 2,
                    py: this.height / 2,
                    fireCooldown: 0,
                    grenades: 3,
                    reloading: 0,
                    spawnTimer: 0.8,
                };
                break;

            case 'street-hoops-pro':
                this.ammo = 60; // Shot clock / quarter timer
                this.modeState = {
                    playerX: 250,
                    playerY: 425,
                    charging: false,
                    power: 0,
                    powerDir: 1,
                    hoopX: 815,
                    hoopY: 225,
                    defenderX: 590,
                    defenderY: 425,
                    defenderVy: 0,
                    streak: 0,
                    balls: [],
                    quarterTimer: 60,
                };
                break;

            case 'moto-xtreme-trials':
                this.ammo = 100; // Nitro
                this.modeState = {
                    bikeX: 200,
                    bikeY: 360,
                    vy: 0,
                    angle: 0,
                    angVel: 0,
                    speed: 260,
                    scrollX: 0,
                    grounded: true,
                    totalRotation: 0,
                    obstacleTimer: 1.1,
                };
                break;

            case 'nova-vanguard-3d':
                this.ammo = 3; // Photon Torpedoes
                this.modeState = {
                    shipX: 160,
                    shipY: this.height / 2,
                    fireCooldown: 0,
                    weaponTier: 1,
                    spawnTimer: 0.65,
                    bossActive: false,
                };
                break;

            case 'arena-tag-royale':
                this.ammo = 100; // Dash energy
                this.modeState = {
                    crownHolder: 0, // 0 = player, 1..3 = bots
                    matchTimer: 75,
                    dashCooldown: 0,
                    empCooldown: 0,
                    empVisual: 0,
                    platforms: [
                        { x: 100, y: 400, w: 240, h: 16 },
                        { x: 620, y: 400, w: 240, h: 16 },
                        { x: 340, y: 295, w: 280, h: 16 },
                        { x: 160, y: 190, w: 200, h: 16 },
                        { x: 600, y: 190, w: 200, h: 16 },
                    ],
                    racers: [
                        { id: 0, name: 'YOU', x: 220, y: 460, vx: 0, vy: 0, color: '#38bdf8', pts: 0, stun: 0 },
                        { id: 1, name: 'CyberViper', x: 720, y: 460, vx: 0, vy: 0, color: '#f43f5e', pts: 0, stun: 0 },
                        { id: 2, name: 'AstralNova', x: 480, y: 260, vx: 0, vy: 0, color: '#a855f7', pts: 0, stun: 0 },
                        { id: 3, name: 'IronTitan', x: 680, y: 150, vx: 0, vy: 0, color: '#10b981', pts: 0, stun: 0 },
                    ],
                };
                break;

            case 'prism-breaker-overdrive':
                this.ammo = 100; // Laser energy
                this.modeState = {
                    paddleX: this.width / 2 - 65,
                    paddleW: 130,
                    balls: [{ x: this.width / 2, y: this.height - 75, vx: 230, vy: -290, r: 8, mega: false }],
                    bricks: [],
                    bossHp: 100,
                    bossX: this.width / 2,
                    bossDir: 1,
                    laserCooldown: 0,
                };
                this.buildPrismBricks();
                break;
        }
    }

    buildPrismBricks() {
        const colors = ['#f43f5e', '#f97316', '#eab308', '#10b981', '#06b6d4', '#8b5cf6'];
        this.modeState.bricks = [];
        const cols = 10;
        const rows = 5;
        const bw = 76;
        const bh = 24;
        const startX = (this.width - cols * (bw + 8)) / 2;
        for (let r = 0; r < rows; r++) {
            for (let c = 0; c < cols; c++) {
                this.modeState.bricks.push({
                    x: startX + c * (bw + 8),
                    y: 95 + r * (bh + 8),
                    w: bw,
                    h: bh,
                    hp: r === 0 ? 2 : 1,
                    color: colors[r % colors.length],
                });
            }
        }
    }

    switchWeapon(idx) {
        if (this.slug === 'cyber-strike-fps' && this.modeState.weapons?.[idx]) {
            this.modeState.weaponIndex = idx;
            const w = this.modeState.weapons[idx];
            this.ammo = w.ammo;
            this.maxAmmo = w.ammo;
            this.sfx.coin();
            this.showBanner(`EQUIPPED: ${w.name.toUpperCase()}`, 1.4);
        }
    }

    handleReloadOrSpecial() {
        if (this.slug === 'cyber-strike-fps') {
            const w = this.modeState.weapons[this.modeState.weaponIndex];
            this.ammo = w.ammo;
            this.modeState.reloading = 0.6;
            this.sfx.powerup();
            this.showBanner('TACTICAL RELOAD COMPLETE!', 1.2);
        } else if (this.slug === 'deadzone-outbreak') {
            this.ammo = this.maxAmmo;
            this.sfx.powerup();
            this.showBanner('MAGAZINE RELOADED!', 1.2);
        }
    }

    handleActionKey(code) {
        if (this.slug === 'shadow-dojo-brawl') {
            this.performFighterAttack(code);
        } else if (this.slug === 'emberfall-survivors') {
            if (code === 'Space') this.performSwordSlash();
            if (code === 'ShiftLeft' || code === 'ShiftRight') this.performDash();
        } else if (this.slug === 'deadzone-outbreak' && (code === 'KeyG' || code === 'Space')) {
            this.throwZombieGrenade();
        } else if (this.slug === 'street-hoops-pro') {
            if (code === 'Space' && !this.modeState.charging) {
                this.modeState.charging = true;
                this.modeState.power = 10;
            } else if (code === 'ShiftLeft' || code === 'KeyK') {
                this.performSlamDunk();
            }
        } else if (this.slug === 'nova-vanguard-3d' && code === 'KeyE') {
            this.firePhotonTorpedo();
        } else if (this.slug === 'arena-tag-royale') {
            if (code === 'Space' || code === 'KeyJ') this.performCrownDash();
            if (code === 'KeyK' || code === 'KeyE') this.performCrownEmp();
        } else if (this.slug === 'prism-breaker-overdrive' && code === 'Space') {
            this.firePrismLasers();
        }
    }

    handlePrimaryFire() {
        if (this.slug === 'cyber-strike-fps') {
            this.fireFpsWeapon();
        } else if (this.slug === 'emberfall-survivors') {
            this.performSwordSlash();
        } else if (this.slug === 'deadzone-outbreak') {
            this.fireZombieBullet();
        } else if (this.slug === 'street-hoops-pro') {
            this.modeState.charging = true;
        } else if (this.slug === 'nova-vanguard-3d') {
            this.fireStarfighterLaser();
        } else if (this.slug === 'prism-breaker-overdrive') {
            this.firePrismLasers();
        } else if (this.slug === 'shadow-dojo-brawl') {
            this.performFighterAttack('KeyJ');
        } else if (this.slug === 'arena-tag-royale') {
            this.performCrownDash();
        }
    }

    // =========================================================================
    // 1. CYBER STRIKE: TACTICAL OPS (FPS)
    // =========================================================================
    fireFpsWeapon() {
        const ms = this.modeState;
        if (ms.fireCooldown > 0 || ms.reloading > 0) return;
        const weapon = ms.weapons[ms.weaponIndex];

        if (this.ammo <= 0) {
            this.handleReloadOrSpecial();
            return;
        }

        this.ammo--;
        ms.fireCooldown = weapon.rate;
        ms.recoil = 14;
        weapon.pellets ? this.sfx.heavyShoot() : this.sfx.shoot();

        const shots = weapon.pellets || 1;
        for (let s = 0; s < shots; s++) {
            const tx = this.mouse.x + (Math.random() - 0.5) * weapon.spread * 2;
            const ty = this.mouse.y + (Math.random() - 0.5) * weapon.spread * 2;
            this.spawnParticles(tx, ty, weapon.color, 6, 120);

            // Check enemy hits (front-to-back)
            for (let i = this.entities.length - 1; i >= 0; i--) {
                const e = this.entities[i];
                const dx = tx - e.x;
                const dy = ty - e.y;
                if (Math.abs(dx) <= e.r && Math.abs(dy) <= e.r * 1.35) {
                    const isHeadshot = dy < -e.r * 0.35;
                    const dmg = isHeadshot ? weapon.dmg * 2.5 : weapon.dmg;
                    e.hp -= dmg;
                    e.hitFlash = 0.15;
                    this.spawnParticles(tx, ty, isHeadshot ? '#fbbf24' : '#ef4444', isHeadshot ? 16 : 8, 190);
                    if (isHeadshot) {
                        this.addFloatingText('HEADSHOT! 2.5x', e.x, e.y - e.r, '#fbbf24');
                    }
                    if (e.hp <= 0) {
                        this.sfx.explosion();
                        this.spawnParticles(e.x, e.y, '#f97316', 24, 240);
                        this.addScore(e.boss ? 450 : (isHeadshot ? 160 : 100), e.x, e.y, e.boss ? 'BOSS DOWN!' : null);
                        this.entities.splice(i, 1);
                    }
                    break;
                }
            }
        }
    }

    updateFps(dt) {
        const ms = this.modeState;
        ms.fireCooldown = Math.max(0, ms.fireCooldown - dt);
        ms.reloading = Math.max(0, ms.reloading - dt);
        ms.recoil = Math.max(0, ms.recoil - dt * 55);

        if (this.mouse.down && ms.weaponIndex === 0) {
            this.fireFpsWeapon();
        }

        ms.spawnTimer -= dt;
        const maxEnemies = Math.min(9, 3 + this.level);
        if (ms.spawnTimer <= 0 && this.entities.length < maxEnemies) {
            const isBoss = this.level % 3 === 0 && !this.entities.some((e) => e.boss);
            const laneY = 230 + Math.random() * 170;
            this.entities.push({
                x: 120 + Math.random() * (this.width - 240),
                y: laneY,
                vx: (Math.random() < 0.5 ? -1 : 1) * (65 + this.level * 12),
                r: isBoss ? 48 : 30,
                hp: isBoss ? 260 + this.level * 60 : 55 + this.level * 16,
                maxHp: isBoss ? 260 + this.level * 60 : 55 + this.level * 16,
                attackTimer: 1.8 + Math.random() * 1.4,
                boss: isBoss,
                type: isBoss ? 'MECH BOSS' : (Math.random() > 0.45 ? 'STRIKER' : 'DRONE'),
                hitFlash: 0,
            });
            ms.spawnTimer = Math.max(0.45, 1.45 - this.level * 0.06);
        }

        for (const e of this.entities) {
            e.x += e.vx * dt;
            if (e.x < 90 || e.x > this.width - 90) e.vx *= -1;
            e.hitFlash = Math.max(0, e.hitFlash - dt);
            e.attackTimer -= dt;
            if (e.attackTimer <= 0) {
                e.attackTimer = Math.max(0.9, 2.3 - this.level * 0.08);
                this.spawnParticles(e.x, e.y, '#ef4444', 10, 130);
                this.damagePlayer(e.boss ? 14 : 7);
            }
        }
    }

    // =========================================================================
    // 2. NITRO APEX GT: STREET HORIZON (RACING)
    // =========================================================================
    updateRacing(dt) {
        const ms = this.modeState;
        const steerSpeed = 370;
        if (this.keys['KeyA'] || this.keys['ArrowLeft']) ms.carX -= steerSpeed * dt;
        if (this.keys['KeyD'] || this.keys['ArrowRight']) ms.carX += steerSpeed * dt;
        ms.carX = Math.max(215, Math.min(this.width - 215, ms.carX));

        ms.nitroActive = (this.keys['Space'] || this.keys['ShiftLeft'] || this.keys['ShiftRight']) && this.ammo > 0;
        if (ms.nitroActive) {
            this.ammo = Math.max(0, this.ammo - 26 * dt);
            ms.speed = Math.min(355, ms.speed + 160 * dt);
            this.spawnParticles(ms.carX - 14, ms.carY + 42, '#38bdf8', 2, 90);
            this.spawnParticles(ms.carX + 14, ms.carY + 42, '#38bdf8', 2, 90);
        } else {
            this.ammo = Math.min(100, this.ammo + 8 * dt);
            if (this.keys['KeyW'] || this.keys['ArrowUp']) {
                ms.speed = Math.min(285, ms.speed + 95 * dt);
            } else if (this.keys['KeyS'] || this.keys['ArrowDown']) {
                ms.speed = Math.max(110, ms.speed - 170 * dt);
            } else {
                ms.speed = Math.max(195, ms.speed - 35 * dt);
            }
        }

        ms.roadOffset = (ms.roadOffset + ms.speed * dt * 2.2) % 80;
        ms.distance += ms.speed * dt * 0.08;
        this.score += Math.round(ms.speed * dt * 0.35);
        if (this.score > this.highScore) this.highScore = this.score;

        ms.checkpointTimer -= dt;
        if (ms.checkpointTimer <= 0) {
            ms.checkpointTimer = 22;
            this.addScore(350, this.width / 2, 140, 'CHECKPOINT TIME EXTENDED!');
            this.sfx.powerup();
        }

        ms.spawnTimer -= dt;
        if (ms.spawnTimer <= 0) {
            const lanes = [265, 405, 555, 695];
            const laneX = lanes[Math.floor(Math.random() * lanes.length)];
            const isNitroOrb = Math.random() < 0.22;
            this.entities.push({
                x: laneX,
                y: -70,
                speed: isNitroOrb ? 110 : (95 + Math.random() * 75),
                isOrb: isNitroOrb,
                passed: false,
                color: isNitroOrb ? '#38bdf8' : ['#ef4444', '#eab308', '#a855f7', '#10b981'][Math.floor(Math.random() * 4)],
            });
            ms.spawnTimer = Math.max(0.28, 0.72 - this.level * 0.03);
        }

        for (let i = this.entities.length - 1; i >= 0; i--) {
            const t = this.entities[i];
            t.y += (ms.speed * 1.45 - t.speed) * dt;

            const dx = Math.abs(t.x - ms.carX);
            const dy = Math.abs(t.y - ms.carY);

            if (t.isOrb && dx < 42 && dy < 52) {
                this.ammo = Math.min(100, this.ammo + 35);
                this.sfx.coin();
                this.addScore(150, t.x, t.y, 'NITRO BOOST');
                this.entities.splice(i, 1);
                continue;
            }

            if (!t.isOrb && dx < 42 && dy < 66) {
                ms.speed = Math.max(110, ms.speed * 0.55);
                this.damagePlayer(18);
                this.spawnParticles(t.x, t.y, '#f97316', 22, 220);
                this.entities.splice(i, 1);
                continue;
            }

            if (!t.isOrb && !t.passed && t.y > ms.carY + 30) {
                t.passed = true;
                if (dx < 85) {
                    this.sfx.coin();
                    this.ammo = Math.min(100, this.ammo + 12);
                    this.addScore(120, ms.carX, ms.carY - 40, 'NEAR MISS!');
                } else {
                    this.addScore(40);
                }
            }

            if (t.y > this.height + 100) {
                this.entities.splice(i, 1);
            }
        }
    }

    // =========================================================================
    // 3. SHADOW DOJO: IRON FIST (FIGHTING)
    // =========================================================================
    performFighterAttack(code) {
        const ms = this.modeState;
        const p = ms.player;
        const r = ms.rival;
        if (p.stateTimer > 0) return;

        p.dir = r.x >= p.x ? 1 : -1;

        if (code === 'KeyL') {
            // Chi Fireball
            p.state = 'fireball';
            p.stateTimer = 0.28;
            this.sfx.shoot();
            this.projectiles.push({
                x: p.x + p.dir * 40,
                y: p.y - 58,
                vx: p.dir * 520,
                r: 16,
                dmg: 14,
                color: '#38bdf8',
            });
            return;
        }

        const isUltimate = code === 'Space' && this.ammo >= 100;
        const isHeavy = code === 'KeyK';
        p.state = isUltimate ? 'ultimate' : (isHeavy ? 'kick' : 'punch');
        p.stateTimer = isHeavy ? 0.26 : 0.18;

        if (isUltimate) {
            this.ammo = 0;
            this.sfx.explosion();
            this.showBanner('DRAGON SHADOW ULTIMATE UNLEASHED!', 1.5);
        } else {
            this.sfx.shoot();
        }

        const reach = isUltimate ? 220 : (isHeavy ? 115 : 92);
        const dist = Math.abs(r.x - p.x);
        if (dist <= reach && Math.abs(r.y - p.y) < 90) {
            const dmg = isUltimate ? 42 : (isHeavy ? 16 : 10);
            r.hp -= dmg;
            r.x += p.dir * (isHeavy ? 38 : 18);
            this.ammo = Math.min(100, this.ammo + 18);
            this.spawnParticles(r.x, r.y - 60, isUltimate ? '#a855f7' : '#f97316', 18, 210);
            this.addScore(isUltimate ? 300 : (isHeavy ? 110 : 65), r.x, r.y - 95, isUltimate ? 'DRAGON KO!' : 'COMBO HIT!');
            this.checkRivalKO();
        }
    }

    checkRivalKO() {
        const ms = this.modeState;
        const r = ms.rival;
        if (r.hp <= 0) {
            this.sfx.powerup();
            ms.round++;
            this.level++;
            this.health = Math.min(100, this.health + 35);
            r.maxHp = 100 + ms.round * 25;
            r.hp = r.maxHp;
            r.x = 700;
            ms.player.x = 220;
            this.addScore(500, this.width / 2, 200, `ROUND ${ms.round - 1} KO VICTORY!`);
            this.showBanner(`ROUND ${ms.round} — NEW GRANDMASTER CHALLENGER!`, 2.2);
        }
    }

    updateFighting(dt) {
        const ms = this.modeState;
        const p = ms.player;
        const r = ms.rival;

        p.stateTimer = Math.max(0, p.stateTimer - dt);
        if (p.stateTimer === 0) p.state = 'idle';

        p.blocking = Boolean(this.keys['KeyS'] || this.keys['ArrowDown']);
        const moveSpeed = p.blocking ? 110 : 280;
        if (this.keys['KeyA'] || this.keys['ArrowLeft']) p.x -= moveSpeed * dt;
        if (this.keys['KeyD'] || this.keys['ArrowRight']) p.x += moveSpeed * dt;
        if ((this.keys['KeyW'] || this.keys['ArrowUp']) && p.y >= ms.groundY) {
            p.vy = -520;
            this.sfx.jump();
        }

        p.vy += 1250 * dt;
        p.y = Math.min(ms.groundY, p.y + p.vy * dt);
        if (p.y >= ms.groundY) p.vy = 0;
        p.x = Math.max(80, Math.min(this.width - 80, p.x));
        r.x = Math.max(80, Math.min(this.width - 80, r.x));

        // Rival AI
        r.dir = p.x < r.x ? -1 : 1;
        const dist = Math.abs(p.x - r.x);
        if (dist > 90) {
            r.x += r.dir * (165 + this.level * 12) * dt;
        }
        r.aiCooldown -= dt;
        if (r.aiCooldown <= 0 && dist <= 105) {
            r.aiCooldown = Math.max(0.45, 1.15 - this.level * 0.05);
            r.state = 'attack';
            r.stateTimer = 0.22;
            const incomingDmg = p.blocking ? 2.5 : 11;
            this.damagePlayer(incomingDmg);
            if (p.blocking) {
                this.addFloatingText('BLOCKED!', p.x, p.y - 90, '#38bdf8');
            }
        }
        r.stateTimer = Math.max(0, (r.stateTimer || 0) - dt);

        // Projectiles
        for (let i = this.projectiles.length - 1; i >= 0; i--) {
            const b = this.projectiles[i];
            b.x += b.vx * dt;
            if (Math.abs(b.x - r.x) < 42 && Math.abs(b.y - (r.y - 55)) < 60) {
                r.hp -= b.dmg;
                this.ammo = Math.min(100, this.ammo + 12);
                this.spawnParticles(b.x, b.y, '#38bdf8', 14, 180);
                this.addScore(80, r.x, r.y - 90, 'CHI BLAST!');
                this.projectiles.splice(i, 1);
                this.checkRivalKO();
                continue;
            }
            if (b.x < 0 || b.x > this.width) this.projectiles.splice(i, 1);
        }
    }

    // =========================================================================
    // 4. EMBERFALL: BLADE & RUIN (ADVENTURE RPG)
    // =========================================================================
    performSwordSlash() {
        const ms = this.modeState;
        if (ms.slashCooldown > 0) return;
        ms.slashCooldown = 0.28;
        ms.slashVisualTimer = 0.22;
        this.sfx.shoot();

        for (let i = this.entities.length - 1; i >= 0; i--) {
            const e = this.entities[i];
            const dist = Math.hypot(e.x - ms.px, e.y - ms.py);
            if (dist <= ms.slashRadius + e.r) {
                e.hp -= 45 + this.level * 10;
                this.spawnParticles(e.x, e.y, '#fbbf24', 12, 180);
                if (e.hp <= 0) {
                    this.pickups.push({ x: e.x, y: e.y, type: Math.random() < 0.18 ? 'chest' : 'xp' });
                    this.addScore(e.boss ? 400 : 95, e.x, e.y);
                    this.entities.splice(i, 1);
                }
            }
        }
    }

    performDash() {
        const ms = this.modeState;
        if (ms.dashCooldown > 0) return;
        ms.dashCooldown = 1.2;
        const angle = Math.atan2(this.mouse.y - ms.py, this.mouse.x - ms.px);
        ms.px = Math.max(50, Math.min(this.width - 50, ms.px + Math.cos(angle) * 135));
        ms.py = Math.max(70, Math.min(this.height - 50, ms.py + Math.sin(angle) * 135));
        this.sfx.jump();
        this.spawnParticles(ms.px, ms.py, '#38bdf8', 16, 170);
    }

    updateAdventure(dt) {
        const ms = this.modeState;
        ms.slashCooldown = Math.max(0, ms.slashCooldown - dt);
        ms.slashVisualTimer = Math.max(0, ms.slashVisualTimer - dt);
        ms.dashCooldown = Math.max(0, ms.dashCooldown - dt);
        ms.orbAngle += dt * 3.4;
        ms.orbCount = Math.min(5, 2 + Math.floor(this.level / 2));

        const spd = 250;
        if (this.keys['KeyA'] || this.keys['ArrowLeft']) ms.px -= spd * dt;
        if (this.keys['KeyD'] || this.keys['ArrowRight']) ms.px += spd * dt;
        if (this.keys['KeyW'] || this.keys['ArrowUp']) ms.py -= spd * dt;
        if (this.keys['KeyS'] || this.keys['ArrowDown']) ms.py += spd * dt;
        ms.px = Math.max(45, Math.min(this.width - 45, ms.px));
        ms.py = Math.max(75, Math.min(this.height - 45, ms.py));

        ms.spawnTimer -= dt;
        if (ms.spawnTimer <= 0 && this.entities.length < 18) {
            const edge = Math.floor(Math.random() * 4);
            const sx = edge === 0 ? 20 : (edge === 1 ? this.width - 20 : Math.random() * this.width);
            const sy = edge === 2 ? 75 : (edge === 3 ? this.height - 20 : 80 + Math.random() * (this.height - 100));
            const isGolem = Math.random() < 0.2;
            this.entities.push({
                x: sx,
                y: sy,
                r: isGolem ? 24 : 15,
                hp: isGolem ? 110 + this.level * 20 : 35 + this.level * 8,
                spd: isGolem ? 75 : 115 + this.level * 6,
                boss: isGolem,
                color: isGolem ? '#a855f7' : '#ef4444',
            });
            ms.spawnTimer = Math.max(0.35, 0.95 - this.level * 0.04);
        }

        // Orbiting Fireballs
        const orbDist = 68;
        for (let i = this.entities.length - 1; i >= 0; i--) {
            const e = this.entities[i];
            const ang = Math.atan2(ms.py - e.y, ms.px - e.x);
            e.x += Math.cos(ang) * e.spd * dt;
            e.y += Math.sin(ang) * e.spd * dt;

            for (let o = 0; o < ms.orbCount; o++) {
                const oa = ms.orbAngle + (o * Math.PI * 2) / ms.orbCount;
                const ox = ms.px + Math.cos(oa) * orbDist;
                const oy = ms.py + Math.sin(oa) * orbDist;
                if (Math.hypot(e.x - ox, e.y - oy) < e.r + 12) {
                    e.hp -= 120 * dt;
                    this.spawnParticles(e.x, e.y, '#f97316', 2, 80);
                }
            }

            if (e.hp <= 0) {
                this.pickups.push({ x: e.x, y: e.y, type: Math.random() < 0.2 ? 'chest' : 'xp' });
                this.addScore(e.boss ? 250 : 80, e.x, e.y);
                this.entities.splice(i, 1);
                continue;
            }

            if (Math.hypot(e.x - ms.px, e.y - ms.py) < e.r + 18) {
                this.damagePlayer(14 * dt);
            }
        }

        // Pickups
        for (let i = this.pickups.length - 1; i >= 0; i--) {
            const p = this.pickups[i];
            const d = Math.hypot(p.x - ms.px, p.y - ms.py);
            if (d < 110) {
                p.x += ((ms.px - p.x) / d) * 260 * dt;
                p.y += ((ms.py - p.y) / d) * 260 * dt;
            }
            if (d < 26) {
                this.sfx.coin();
                if (p.type === 'chest') {
                    this.health = Math.min(100, this.health + 25);
                    this.addScore(250, p.x, p.y, 'GOLDEN RELIC!');
                } else {
                    this.addScore(45, p.x, p.y);
                }
                this.pickups.splice(i, 1);
            }
        }
    }

    // =========================================================================
    // 5. DEADZONE: OUTBREAK 2099 (ZOMBIE SURVIVAL)
    // =========================================================================
    fireZombieBullet() {
        const ms = this.modeState;
        if (ms.fireCooldown > 0) return;
        if (this.ammo <= 0) {
            this.handleReloadOrSpecial();
            return;
        }
        this.ammo--;
        ms.fireCooldown = 0.11;
        this.sfx.shoot();
        const ang = Math.atan2(this.mouse.y - ms.py, this.mouse.x - ms.px);
        this.projectiles.push({
            x: ms.px + Math.cos(ang) * 22,
            y: ms.py + Math.sin(ang) * 22,
            vx: Math.cos(ang) * 680,
            vy: Math.sin(ang) * 680,
        });
    }

    throwZombieGrenade() {
        const ms = this.modeState;
        if (ms.grenades <= 0) return;
        ms.grenades--;
        this.sfx.explosion();
        const gx = this.mouse.x;
        const gy = this.mouse.y;
        this.spawnParticles(gx, gy, '#f97316', 36, 280);
        this.showBanner('FRAG GRENADE DETONATED!', 1.2);

        for (let i = this.entities.length - 1; i >= 0; i--) {
            const z = this.entities[i];
            if (Math.hypot(z.x - gx, z.y - gy) <= 155) {
                this.addScore(110, z.x, z.y, 'BOOM!');
                this.entities.splice(i, 1);
            }
        }
    }

    updateZombie(dt) {
        const ms = this.modeState;
        ms.fireCooldown = Math.max(0, ms.fireCooldown - dt);
        if (this.mouse.down) this.fireZombieBullet();

        const spd = 235;
        if (this.keys['KeyA'] || this.keys['ArrowLeft']) ms.px -= spd * dt;
        if (this.keys['KeyD'] || this.keys['ArrowRight']) ms.px += spd * dt;
        if (this.keys['KeyW'] || this.keys['ArrowUp']) ms.py -= spd * dt;
        if (this.keys['KeyS'] || this.keys['ArrowDown']) ms.py += spd * dt;
        ms.px = Math.max(40, Math.min(this.width - 40, ms.px));
        ms.py = Math.max(75, Math.min(this.height - 40, ms.py));

        ms.spawnTimer -= dt;
        if (ms.spawnTimer <= 0 && this.entities.length < 22) {
            const angle = Math.random() * Math.PI * 2;
            const boomer = Math.random() < 0.22;
            this.entities.push({
                x: this.width / 2 + Math.cos(angle) * 520,
                y: this.height / 2 + Math.sin(angle) * 320,
                r: boomer ? 22 : 15,
                hp: boomer ? 85 : 32 + this.level * 6,
                spd: boomer ? 78 : 115 + Math.random() * 45 + this.level * 5,
                boomer,
            });
            ms.spawnTimer = Math.max(0.3, 0.85 - this.level * 0.04);
        }

        for (let i = this.projectiles.length - 1; i >= 0; i--) {
            const b = this.projectiles[i];
            b.x += b.vx * dt;
            b.y += b.vy * dt;
            let hit = false;
            for (let j = this.entities.length - 1; j >= 0; j--) {
                const z = this.entities[j];
                if (Math.hypot(b.x - z.x, b.y - z.y) <= z.r + 6) {
                    z.hp -= 36;
                    hit = true;
                    this.spawnParticles(b.x, b.y, '#10b981', 7, 130);
                    if (z.hp <= 0) {
                        if (Math.random() < 0.16) {
                            this.pickups.push({ x: z.x, y: z.y });
                        }
                        this.addScore(z.boomer ? 160 : 75, z.x, z.y);
                        this.entities.splice(j, 1);
                    }
                    break;
                }
            }
            if (hit || b.x < 0 || b.x > this.width || b.y < 0 || b.y > this.height) {
                this.projectiles.splice(i, 1);
            }
        }

        for (const z of this.entities) {
            const a = Math.atan2(ms.py - z.y, ms.px - z.x);
            z.x += Math.cos(a) * z.spd * dt;
            z.y += Math.sin(a) * z.spd * dt;
            if (Math.hypot(z.x - ms.px, z.y - ms.py) < z.r + 16) {
                this.damagePlayer(18 * dt);
            }
        }

        for (let i = this.pickups.length - 1; i >= 0; i--) {
            const p = this.pickups[i];
            if (Math.hypot(p.x - ms.px, p.y - ms.py) < 28) {
                this.ammo = this.maxAmmo;
                ms.grenades = Math.min(5, ms.grenades + 1);
                this.health = Math.min(100, this.health + 15);
                this.sfx.powerup();
                this.addScore(100, p.x, p.y, 'SUPPLY DROP!');
                this.pickups.splice(i, 1);
            }
        }
    }

    // =========================================================================
    // 6. STREET HOOPS 3X3: PRO COURT (SPORTS)
    // =========================================================================
    releaseHoopsShot() {
        const ms = this.modeState;
        if (!ms.charging) return;
        ms.charging = false;

        // Perfect release is around power 72..88
        const accuracy = 1 - Math.min(1, Math.abs(ms.power - 80) / 55);
        const isPerfect = Math.abs(ms.power - 80) <= 10;
        const targetX = ms.hoopX + (isPerfect ? 0 : (Math.random() - 0.5) * (1 - accuracy) * 95);
        const flightTime = 0.85;
        const startX = ms.playerX + 18;
        const startY = ms.playerY - 62;
        const gravity = 980;
        const vx = (targetX - startX) / flightTime;
        const vy = (ms.hoopY - startY - 0.5 * gravity * flightTime * flightTime) / flightTime;

        this.sfx.jump();
        ms.balls.push({
            x: startX,
            y: startY,
            vx,
            vy,
            scored: false,
            isThree: ms.playerX < 420,
            isPerfect,
        });
    }

    performSlamDunk() {
        const ms = this.modeState;
        if (Math.abs(ms.playerX - ms.defenderX) < 55) {
            this.addFloatingText('DEFENDED! STEP BACK!', ms.playerX, ms.playerY - 90, '#ef4444');
            return;
        }
        ms.playerX = ms.hoopX - 50;
        this.sfx.explosion();
        this.spawnParticles(ms.hoopX, ms.hoopY, '#f97316', 28, 250);
        ms.streak++;
        this.addScore(ms.streak >= 3 ? 400 : 200, ms.hoopX, ms.hoopY, 'THUNDER SLAM DUNK!');
        ms.playerX = 240;
    }

    updateHoops(dt) {
        const ms = this.modeState;
        if (this.keys['KeyA'] || this.keys['ArrowLeft']) ms.playerX -= 250 * dt;
        if (this.keys['KeyD'] || this.keys['ArrowRight']) ms.playerX += 250 * dt;
        ms.playerX = Math.max(110, Math.min(680, ms.playerX));

        if (ms.charging) {
            ms.power += ms.powerDir * 135 * dt;
            if (ms.power >= 100) {
                ms.power = 100;
                ms.powerDir = -1;
            } else if (ms.power <= 10) {
                ms.power = 10;
                ms.powerDir = 1;
            }
        }

        // Defender AI patrols & jumps
        const targetDefX = Math.max(430, Math.min(720, ms.playerX + 120));
        ms.defenderX += Math.sign(targetDefX - ms.defenderX) * 150 * dt;

        for (let i = ms.balls.length - 1; i >= 0; i--) {
            const b = ms.balls[i];
            b.x += b.vx * dt;
            b.y += b.vy * dt;
            b.vy += 980 * dt;

            if (!b.scored && b.vy > 0 && Math.hypot(b.x - ms.hoopX, b.y - ms.hoopY) < 28) {
                b.scored = true;
                ms.streak++;
                this.sfx.coin();
                const onFire = ms.streak >= 3;
                const pts = (b.isThree ? 300 : 200) * (onFire ? 2 : 1);
                this.spawnParticles(ms.hoopX, ms.hoopY, onFire ? '#f97316' : '#38bdf8', 24, 210);
                this.addScore(pts, ms.hoopX, ms.hoopY - 25, onFire ? 'ON FIRE SPLASH 3PT!' : (b.isThree ? 'SPLASH 3-POINTER!' : 'SWISH!'));
            }

            if (b.y > 460) {
                if (!b.scored) ms.streak = 0;
                ms.balls.splice(i, 1);
            }
        }
    }

    // =========================================================================
    // 7. MOTO XTREME: GRAVITY TRIALS (MOTORCYCLE)
    // =========================================================================
    terrainHeightAt(worldX) {
        return 415 - Math.sin(worldX * 0.0065) * 58 - Math.cos(worldX * 0.015) * 32;
    }

    updateMoto(dt) {
        const ms = this.modeState;
        const nitro = (this.keys['Space'] || this.keys['ShiftLeft']) && this.ammo > 0;
        if (nitro) {
            this.ammo = Math.max(0, this.ammo - 25 * dt);
            ms.speed = Math.min(460, ms.speed + 180 * dt);
            this.spawnParticles(ms.bikeX - 30, ms.bikeY + 10, '#f97316', 2, 110);
        } else {
            this.ammo = Math.min(100, this.ammo + 10 * dt);
            ms.speed = (this.keys['KeyW'] || this.keys['KeyD'] || this.keys['ArrowRight'])
                ? Math.min(350, ms.speed + 110 * dt)
                : Math.max(190, ms.speed - 50 * dt);
        }

        ms.scrollX += ms.speed * dt;
        this.score += Math.round(ms.speed * dt * 0.25);
        if (this.score > this.highScore) this.highScore = this.score;

        const groundY = this.terrainHeightAt(ms.scrollX + ms.bikeX);
        const nextGroundY = this.terrainHeightAt(ms.scrollX + ms.bikeX + 20);
        const slopeAngle = Math.atan2(nextGroundY - groundY, 20);

        if (ms.bikeY < groundY - 4) {
            ms.grounded = false;
            ms.vy += 820 * dt;
            if (this.keys['KeyA'] || this.keys['ArrowLeft']) {
                ms.angle -= 4.8 * dt;
                ms.totalRotation += 4.8 * dt;
            }
            if (this.keys['KeyD'] || this.keys['ArrowRight']) {
                ms.angle += 4.8 * dt;
                ms.totalRotation += 4.8 * dt;
            }
            if (ms.totalRotation >= Math.PI * 1.85) {
                ms.totalRotation = 0;
                this.ammo = Math.min(100, this.ammo + 45);
                this.sfx.powerup();
                this.addScore(450, ms.bikeX, ms.bikeY - 50, '360° STUNT FLIP!');
            }
        }

        ms.bikeY += ms.vy * dt;
        if (ms.bikeY >= groundY) {
            if (!ms.grounded) {
                const normAngle = Math.atan2(Math.sin(ms.angle), Math.cos(ms.angle));
                if (Math.abs(normAngle) > 1.35) {
                    this.damagePlayer(25);
                    this.addFloatingText('HARD CRASH!', ms.bikeX, ms.bikeY - 40, '#ef4444');
                } else if (ms.vy > 220) {
                    this.addScore(140, ms.bikeX, ms.bikeY - 40, 'CLEAN LANDING!');
                }
                ms.vy = slopeAngle < -0.25 ? -360 : 0;
                ms.totalRotation = 0;
            }
            ms.bikeY = groundY;
            ms.grounded = ms.vy === 0;
            ms.angle = slopeAngle;
        }
    }

    // =========================================================================
    // 8. NOVA VANGUARD: STARFIGHTER ASSAULT (SPACE)
    // =========================================================================
    fireStarfighterLaser() {
        const ms = this.modeState;
        if (ms.fireCooldown > 0) return;
        ms.fireCooldown = 0.13;
        this.sfx.shoot();

        const offsets = this.level >= 2 ? [-16, 0, 16] : [-12, 12];
        offsets.forEach((oy) => {
            this.projectiles.push({
                x: ms.shipX + 32,
                y: ms.shipY + oy,
                vx: 720,
                vy: oy * 2.5,
                dmg: 32,
            });
        });
    }

    firePhotonTorpedo() {
        if (this.ammo <= 0) return;
        this.ammo--;
        this.sfx.explosion();
        this.showBanner('PHOTON TORPEDO BARRAGE!', 1.2);
        for (let i = this.entities.length - 1; i >= 0; i--) {
            const e = this.entities[i];
            e.hp -= 160;
            this.spawnParticles(e.x, e.y, '#38bdf8', 20, 220);
            if (e.hp <= 0) {
                this.addScore(e.boss ? 500 : 120, e.x, e.y);
                this.entities.splice(i, 1);
            }
        }
    }

    updateSpace(dt) {
        const ms = this.modeState;
        ms.fireCooldown = Math.max(0, ms.fireCooldown - dt);
        if (this.mouse.down || this.keys['Space']) this.fireStarfighterLaser();

        const spd = 310;
        if (this.keys['KeyW'] || this.keys['ArrowUp']) ms.shipY -= spd * dt;
        if (this.keys['KeyS'] || this.keys['ArrowDown']) ms.shipY += spd * dt;
        if (this.keys['KeyA'] || this.keys['ArrowLeft']) ms.shipX -= spd * dt;
        if (this.keys['KeyD'] || this.keys['ArrowRight']) ms.shipX += spd * dt;
        ms.shipX = Math.max(60, Math.min(this.width - 180, ms.shipX));
        ms.shipY = Math.max(75, Math.min(this.height - 55, ms.shipY));

        ms.spawnTimer -= dt;
        if (ms.spawnTimer <= 0 && this.entities.length < 14) {
            const isDreadnought = this.level % 2 === 0 && !this.entities.some((e) => e.boss);
            this.entities.push({
                x: this.width + 50,
                y: 95 + Math.random() * (this.height - 165),
                vx: isDreadnought ? -55 : -(150 + Math.random() * 110 + this.level * 12),
                vy: (Math.random() - 0.5) * 80,
                r: isDreadnought ? 46 : 22,
                hp: isDreadnought ? 320 + this.level * 60 : 50 + this.level * 10,
                maxHp: isDreadnought ? 320 + this.level * 60 : 50 + this.level * 10,
                boss: isDreadnought,
            });
            ms.spawnTimer = Math.max(0.32, 0.85 - this.level * 0.04);
        }

        for (let i = this.projectiles.length - 1; i >= 0; i--) {
            const b = this.projectiles[i];
            b.x += b.vx * dt;
            b.y += b.vy * dt;
            let hit = false;
            for (let j = this.entities.length - 1; j >= 0; j--) {
                const e = this.entities[j];
                if (Math.hypot(b.x - e.x, b.y - e.y) <= e.r + 8) {
                    e.hp -= b.dmg;
                    hit = true;
                    this.spawnParticles(b.x, b.y, '#38bdf8', 8, 150);
                    if (e.hp <= 0) {
                        this.sfx.explosion();
                        this.spawnParticles(e.x, e.y, '#f97316', 24, 240);
                        if (Math.random() < 0.2) this.ammo = Math.min(5, this.ammo + 1);
                        this.addScore(e.boss ? 500 : 110, e.x, e.y, e.boss ? 'DREADNOUGHT DESTROYED!' : null);
                        this.entities.splice(j, 1);
                    }
                    break;
                }
            }
            if (hit || b.x > this.width + 40) this.projectiles.splice(i, 1);
        }

        for (let i = this.entities.length - 1; i >= 0; i--) {
            const e = this.entities[i];
            if (e.boss && e.x <= this.width - 140) {
                e.y += Math.sin(this.timeElapsed * 2.5) * 90 * dt;
            } else {
                e.x += e.vx * dt;
                e.y += e.vy * dt;
            }
            if (Math.hypot(e.x - ms.shipX, e.y - ms.shipY) < e.r + 22) {
                this.damagePlayer(20);
                this.entities.splice(i, 1);
                continue;
            }
            if (e.x < -60) this.entities.splice(i, 1);
        }
    }

    // =========================================================================
    // 9. CYBER TAG: CROWN ROYALE (MULTIPLAYER ARENA)
    // =========================================================================
    performCrownDash() {
        const ms = this.modeState;
        if (ms.dashCooldown > 0) return;
        ms.dashCooldown = 0.9;
        const p = ms.racers[0];
        const dir = (this.keys['KeyA'] || this.keys['ArrowLeft']) ? -1 : 1;
        p.vx = dir * 560;
        this.sfx.jump();
        this.spawnParticles(p.x, p.y, '#38bdf8', 14, 160);
    }

    performCrownEmp() {
        const ms = this.modeState;
        if (ms.empCooldown > 0) return;
        ms.empCooldown = 3.0;
        ms.empVisual = 0.35;
        const p = ms.racers[0];
        this.sfx.explosion();
        ms.racers.slice(1).forEach((bot) => {
            if (Math.hypot(bot.x - p.x, bot.y - p.y) < 170) {
                bot.vx = Math.sign(bot.x - p.x) * 480;
                bot.vy = -320;
                bot.stun = 1.1;
                if (ms.crownHolder === bot.id) {
                    ms.crownHolder = 0;
                    this.addScore(180, p.x, p.y - 40, 'EMP CROWN STEAL!');
                }
            }
        });
    }

    updateCrownRoyale(dt) {
        const ms = this.modeState;
        ms.dashCooldown = Math.max(0, ms.dashCooldown - dt);
        ms.empCooldown = Math.max(0, ms.empCooldown - dt);
        ms.empVisual = Math.max(0, ms.empVisual - dt);

        const p = ms.racers[0];
        if (this.keys['KeyA'] || this.keys['ArrowLeft']) p.vx = -270;
        else if (this.keys['KeyD'] || this.keys['ArrowRight']) p.vx = 270;
        else p.vx *= 0.86;

        if ((this.keys['KeyW'] || this.keys['ArrowUp']) && p.grounded) {
            p.vy = -560;
            p.grounded = false;
            this.sfx.jump();
        }

        // Update all 4 racers
        const crownCarrier = ms.racers[ms.crownHolder];
        crownCarrier.pts += 30 * dt;
        if (ms.crownHolder === 0) {
            this.score += Math.round(35 * dt);
            if (this.score > this.highScore) this.highScore = this.score;
        }

        ms.racers.forEach((r, idx) => {
            r.stun = Math.max(0, r.stun - dt);
            if (idx > 0 && r.stun <= 0) {
                const target = ms.crownHolder === idx ? { x: this.width / 2, y: 180 } : crownCarrier;
                const dir = ms.crownHolder === idx ? -Math.sign(p.x - r.x) : Math.sign(target.x - r.x);
                r.vx = dir * (195 + idx * 14);
                if (r.grounded && (Math.random() < 0.03 || target.y < r.y - 40)) {
                    r.vy = -540;
                    r.grounded = false;
                }
            }

            r.vy += 1200 * dt;
            r.x = Math.max(50, Math.min(this.width - 50, r.x + r.vx * dt));
            r.y += r.vy * dt;
            r.grounded = false;

            // Ground & platforms
            if (r.y >= 485) {
                r.y = 485;
                r.vy = 0;
                r.grounded = true;
            }
            for (const plat of ms.platforms) {
                if (r.vy >= 0 && r.x >= plat.x && r.x <= plat.x + plat.w && r.y >= plat.y && r.y <= plat.y + 24) {
                    r.y = plat.y;
                    r.vy = 0;
                    r.grounded = true;
                }
            }
        });

        // Tag collision checks
        for (let i = 0; i < ms.racers.length; i++) {
            if (i === ms.crownHolder) continue;
            const challenger = ms.racers[i];
            if (challenger.stun <= 0 && Math.hypot(challenger.x - crownCarrier.x, challenger.y - crownCarrier.y) < 38) {
                crownCarrier.stun = 0.9;
                crownCarrier.vy = -280;
                ms.crownHolder = i;
                this.sfx.coin();
                if (i === 0) {
                    this.addScore(160, p.x, p.y - 40, 'CROWN SEIZED!');
                } else {
                    this.showBanner(`${challenger.name.toUpperCase()} STOLE THE CROWN!`, 1.4);
                }
                break;
            }
        }
    }

    // =========================================================================
    // 10. PRISM BREAKER: NEON OVERDRIVE (ARCADE)
    // =========================================================================
    firePrismLasers() {
        const ms = this.modeState;
        if (ms.laserCooldown > 0) return;
        ms.laserCooldown = 0.25;
        this.sfx.shoot();
        this.projectiles.push(
            { x: ms.paddleX + 12, y: this.height - 55, vy: -580 },
            { x: ms.paddleX + ms.paddleW - 12, y: this.height - 55, vy: -580 }
        );
    }

    updatePrismBreaker(dt) {
        const ms = this.modeState;
        ms.laserCooldown = Math.max(0, ms.laserCooldown - dt);

        if (this.keys['KeyA'] || this.keys['ArrowLeft']) ms.paddleX -= 460 * dt;
        else if (this.keys['KeyD'] || this.keys['ArrowRight']) ms.paddleX += 460 * dt;
        else ms.paddleX += (this.mouse.x - (ms.paddleX + ms.paddleW / 2)) * 12 * dt;
        ms.paddleX = Math.max(24, Math.min(this.width - ms.paddleW - 24, ms.paddleX));

        // Sentinel Boss at top
        ms.bossX += ms.bossDir * 140 * dt;
        if (ms.bossX < 180 || ms.bossX > this.width - 180) ms.bossDir *= -1;

        for (let i = this.projectiles.length - 1; i >= 0; i--) {
            const l = this.projectiles[i];
            l.y += l.vy * dt;
            let hit = false;
            for (let j = ms.bricks.length - 1; j >= 0; j--) {
                const br = ms.bricks[j];
                if (l.x >= br.x && l.x <= br.x + br.w && l.y >= br.y && l.y <= br.y + br.h) {
                    br.hp--;
                    hit = true;
                    if (br.hp <= 0) {
                        this.spawnParticles(br.x + br.w / 2, br.y + br.h / 2, br.color, 10, 150);
                        this.addScore(65, br.x + br.w / 2, br.y);
                        ms.bricks.splice(j, 1);
                    }
                    break;
                }
            }
            if (hit || l.y < 50) this.projectiles.splice(i, 1);
        }

        for (let i = ms.balls.length - 1; i >= 0; i--) {
            const b = ms.balls[i];
            b.x += b.vx * dt;
            b.y += b.vy * dt;

            if (b.x < 24 || b.x > this.width - 24) {
                b.vx *= -1;
                this.sfx.hit();
            }
            if (b.y < 65) {
                b.vy = Math.abs(b.vy);
                this.sfx.hit();
            }

            // Paddle bounce
            const py = this.height - 48;
            if (b.vy > 0 && b.y + b.r >= py && b.y - b.r <= py + 18 && b.x >= ms.paddleX - 8 && b.x <= ms.paddleX + ms.paddleW + 8) {
                const hitOffset = (b.x - (ms.paddleX + ms.paddleW / 2)) / (ms.paddleW / 2);
                const spd = Math.min(540, Math.hypot(b.vx, b.vy) * 1.03);
                b.vx = hitOffset * spd * 0.82;
                b.vy = -Math.sqrt(Math.max(9000, spd * spd - b.vx * b.vx));
                this.sfx.coin();
            }

            // Brick collisions
            for (let j = ms.bricks.length - 1; j >= 0; j--) {
                const br = ms.bricks[j];
                if (b.x + b.r >= br.x && b.x - b.r <= br.x + br.w && b.y + b.r >= br.y && b.y - b.r <= br.y + br.h) {
                    b.vy *= -1;
                    br.hp--;
                    this.sfx.hit();
                    if (br.hp <= 0) {
                        this.spawnParticles(br.x + br.w / 2, br.y + br.h / 2, br.color, 14, 170);
                        this.addScore(90, br.x + br.w / 2, br.y);
                        if (Math.random() < 0.16 && ms.balls.length < 4) {
                            ms.balls.push({ x: b.x, y: b.y, vx: -b.vx, vy: -280, r: 8 });
                            this.showBanner('MULTI-BALL OVERDRIVE!', 1.4);
                        }
                        ms.bricks.splice(j, 1);
                    }
                    break;
                }
            }

            if (b.y > this.height + 20) {
                ms.balls.splice(i, 1);
            }
        }

        if (ms.balls.length === 0) {
            this.damagePlayer(34);
            if (this.state === 'playing') {
                ms.balls.push({ x: ms.paddleX + ms.paddleW / 2, y: this.height - 65, vx: 220, vy: -290, r: 8 });
            }
        }

        if (ms.bricks.length === 0) {
            this.sfx.powerup();
            this.addScore(600, this.width / 2, 220, 'STAGE CLEARED!');
            this.buildPrismBricks();
        }
    }

    // =========================================================================
    // MAIN LOOP & RENDERING
    // =========================================================================
    loop(now) {
        const dt = Math.min(0.05, (now - this.lastFrameTime) / 1000);
        this.lastFrameTime = now;

        if (this.state === 'playing') {
            this.timeElapsed += dt;
            if (this.comboTimer > 0) {
                this.comboTimer -= dt;
                if (this.comboTimer <= 0) this.combo = 0;
            }
            if (this.bannerTimer > 0) this.bannerTimer -= dt;

            switch (this.slug) {
                case 'cyber-strike-fps': this.updateFps(dt); break;
                case 'nitro-apex-gt': this.updateRacing(dt); break;
                case 'shadow-dojo-brawl': this.updateFighting(dt); break;
                case 'emberfall-survivors': this.updateAdventure(dt); break;
                case 'deadzone-outbreak': this.updateZombie(dt); break;
                case 'street-hoops-pro': this.updateHoops(dt); break;
                case 'moto-xtreme-trials': this.updateMoto(dt); break;
                case 'nova-vanguard-3d': this.updateSpace(dt); break;
                case 'arena-tag-royale': this.updateCrownRoyale(dt); break;
                case 'prism-breaker-overdrive': this.updatePrismBreaker(dt); break;
            }

            // Update particles & floating texts
            for (let i = this.particles.length - 1; i >= 0; i--) {
                const p = this.particles[i];
                p.x += p.vx * dt;
                p.y += p.vy * dt;
                p.alpha -= p.decay * dt;
                if (p.alpha <= 0) this.particles.splice(i, 1);
            }
            for (let i = this.floatingTexts.length - 1; i >= 0; i--) {
                const f = this.floatingTexts[i];
                f.y += f.vy * dt;
                f.alpha -= 0.9 * dt;
                if (f.alpha <= 0) this.floatingTexts.splice(i, 1);
            }
        }

        this.render();
        this.mouse.clicked = false;
        requestAnimationFrame(this.loop);
    }

    render() {
        const ctx = this.ctx;
        ctx.clearRect(0, 0, this.width, this.height);

        switch (this.slug) {
            case 'cyber-strike-fps': this.renderFps(ctx); break;
            case 'nitro-apex-gt': this.renderRacing(ctx); break;
            case 'shadow-dojo-brawl': this.renderFighting(ctx); break;
            case 'emberfall-survivors': this.renderAdventure(ctx); break;
            case 'deadzone-outbreak': this.renderZombie(ctx); break;
            case 'street-hoops-pro': this.renderHoops(ctx); break;
            case 'moto-xtreme-trials': this.renderMoto(ctx); break;
            case 'nova-vanguard-3d': this.renderSpace(ctx); break;
            case 'arena-tag-royale': this.renderCrownRoyale(ctx); break;
            case 'prism-breaker-overdrive': this.renderPrismBreaker(ctx); break;
        }

        // Render particles
        for (const p of this.particles) {
            ctx.save();
            ctx.globalAlpha = Math.max(0, p.alpha);
            ctx.fillStyle = p.color;
            ctx.beginPath();
            ctx.arc(p.x, p.y, p.radius, 0, Math.PI * 2);
            ctx.fill();
            ctx.restore();
        }

        // Render floating texts
        for (const f of this.floatingTexts) {
            ctx.save();
            ctx.globalAlpha = Math.max(0, f.alpha);
            ctx.font = '800 17px "Outfit", sans-serif';
            ctx.fillStyle = f.color;
            ctx.textAlign = 'center';
            ctx.fillText(f.text, f.x, f.y);
            ctx.restore();
        }

        this.renderHud(ctx);

        if (this.state !== 'playing') {
            this.renderOverlay(ctx);
        }
    }

    renderFps(ctx) {
        const grad = ctx.createLinearGradient(0, 0, 0, this.height);
        grad.addColorStop(0, '#090d1a');
        grad.addColorStop(0.55, '#1e1b4b');
        grad.addColorStop(1, '#111827');
        ctx.fillStyle = grad;
        ctx.fillRect(0, 0, this.width, this.height);

        // Neo-Tokyo skyline & cyber grid floor
        ctx.fillStyle = '#0f172a';
        for (let i = 0; i < 12; i++) {
            ctx.fillRect(i * 85, 110 + (i % 3) * 28, 65, 130);
        }
        ctx.strokeStyle = 'rgba(56, 189, 248, 0.18)';
        ctx.lineWidth = 1;
        for (let y = 240; y < this.height; y += 35) {
            ctx.beginPath();
            ctx.moveTo(0, y);
            ctx.lineTo(this.width, y);
            ctx.stroke();
        }

        // Enemies
        for (const e of this.entities) {
            ctx.save();
            ctx.translate(e.x, e.y);
            ctx.fillStyle = e.hitFlash > 0 ? '#ffffff' : (e.boss ? '#a855f7' : '#ef4444');
            // Body
            ctx.beginPath();
            ctx.roundRect(-e.r * 0.75, -e.r * 0.4, e.r * 1.5, e.r * 1.5, 8);
            ctx.fill();
            // Head (Headshot target)
            ctx.fillStyle = e.hitFlash > 0 ? '#fde047' : '#1e293b';
            ctx.beginPath();
            ctx.arc(0, -e.r * 0.72, e.r * 0.45, 0, Math.PI * 2);
            ctx.fill();
            // Glowing visor
            ctx.fillStyle = '#38bdf8';
            ctx.fillRect(-e.r * 0.3, -e.r * 0.8, e.r * 0.6, 5);
            // HP bar
            ctx.fillStyle = 'rgba(0,0,0,0.65)';
            ctx.fillRect(-e.r, -e.r * 1.4, e.r * 2, 6);
            ctx.fillStyle = e.boss ? '#c084fc' : '#22c55e';
            ctx.fillRect(-e.r, -e.r * 1.4, e.r * 2 * Math.max(0, e.hp / e.maxHp), 6);
            ctx.restore();
        }

        // First-Person Weapon Model + Crosshair
        const ms = this.modeState;
        const w = ms.weapons[ms.weaponIndex];
        const gunX = this.width / 2 + (this.mouse.x - this.width / 2) * 0.25;
        const gunY = this.height - 20 + ms.recoil;
        ctx.fillStyle = '#1e293b';
        ctx.beginPath();
        ctx.moveTo(gunX - 30, gunY);
        ctx.lineTo(gunX + 45, gunY);
        ctx.lineTo(gunX + 18, gunY - 105);
        ctx.lineTo(gunX - 14, gunY - 105);
        ctx.closePath();
        ctx.fill();
        ctx.fillStyle = w.color;
        ctx.fillRect(gunX - 4, gunY - 100, 10, 65);

        // Tactical Crosshair
        ctx.strokeStyle = w.color;
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.arc(this.mouse.x, this.mouse.y, 18, 0, Math.PI * 2);
        ctx.moveTo(this.mouse.x - 26, this.mouse.y);
        ctx.lineTo(this.mouse.x + 26, this.mouse.y);
        ctx.moveTo(this.mouse.x, this.mouse.y - 26);
        ctx.lineTo(this.mouse.x, this.mouse.y + 26);
        ctx.stroke();
    }

    renderRacing(ctx) {
        const ms = this.modeState;
        ctx.fillStyle = '#090d16';
        ctx.fillRect(0, 0, this.width, this.height);

        // Highway asphalt
        ctx.fillStyle = '#1e293b';
        ctx.fillRect(180, 0, this.width - 360, this.height);

        // Neon curbs
        ctx.fillStyle = '#f97316';
        ctx.fillRect(172, 0, 10, this.height);
        ctx.fillRect(this.width - 182, 0, 10, this.height);

        // Scrolling lane markers
        ctx.fillStyle = 'rgba(255,255,255,0.7)';
        for (let lane = 1; lane <= 3; lane++) {
            const lx = 180 + lane * ((this.width - 360) / 4);
            for (let y = -80 + ms.roadOffset; y < this.height; y += 80) {
                ctx.fillRect(lx - 3, y, 6, 42);
            }
        }

        // Traffic & Nitro Orbs
        for (const t of this.entities) {
            if (t.isOrb) {
                ctx.fillStyle = '#38bdf8';
                ctx.beginPath();
                ctx.arc(t.x, t.y, 18, 0, Math.PI * 2);
                ctx.fill();
            } else {
                ctx.fillStyle = t.color;
                ctx.beginPath();
                ctx.roundRect(t.x - 24, t.y - 42, 48, 84, 10);
                ctx.fill();
                ctx.fillStyle = '#0f172a';
                ctx.fillRect(t.x - 18, t.y - 22, 36, 36);
            }
        }

        // Player Supercar
        ctx.save();
        ctx.translate(ms.carX, ms.carY);
        ctx.fillStyle = ms.nitroActive ? '#38bdf8' : '#f97316';
        ctx.beginPath();
        ctx.roundRect(-26, -46, 52, 92, 12);
        ctx.fill();
        ctx.fillStyle = '#0f172a';
        ctx.fillRect(-20, -20, 40, 42);
        ctx.fillStyle = '#ef4444';
        ctx.fillRect(-22, 38, 14, 6);
        ctx.fillRect(8, 38, 14, 6);
        ctx.restore();

        // Speedometer badge
        ctx.fillStyle = 'rgba(15, 23, 42, 0.88)';
        ctx.fillRect(24, this.height - 92, 135, 68);
        ctx.font = '900 28px "Outfit", sans-serif';
        ctx.fillStyle = ms.nitroActive ? '#38bdf8' : '#fbbf24';
        ctx.fillText(`${Math.round(ms.speed)} KM/H`, 36, this.height - 52);
        ctx.font = '700 12px "Inter", sans-serif';
        ctx.fillStyle = '#94a3b8';
        ctx.fillText(`CHECKPOINT: ${ms.checkpointTimer.toFixed(1)}s`, 36, this.height - 32);
    }

    renderFighting(ctx) {
        const ms = this.modeState;
        const grad = ctx.createLinearGradient(0, 0, 0, this.height);
        grad.addColorStop(0, '#18091c');
        grad.addColorStop(0.7, '#3b0764');
        grad.addColorStop(1, '#090d16');
        ctx.fillStyle = grad;
        ctx.fillRect(0, 0, this.width, this.height);

        // Crimson Moon & Dojo Floor
        ctx.fillStyle = 'rgba(244, 63, 94, 0.25)';
        ctx.beginPath();
        ctx.arc(this.width / 2, 210, 120, 0, Math.PI * 2);
        ctx.fill();

        ctx.fillStyle = '#1e1b4b';
        ctx.fillRect(0, ms.groundY, this.width, this.height - ms.groundY);

        const drawFighter = (f, color, name, isPlayer) => {
            ctx.save();
            ctx.translate(f.x, f.y);
            ctx.fillStyle = color;
            // Torso & Head
            ctx.fillRect(-20, -78, 40, 52);
            ctx.beginPath();
            ctx.arc(0, -94, 16, 0, Math.PI * 2);
            ctx.fill();
            // Legs
            ctx.fillRect(-18, -26, 14, 26);
            ctx.fillRect(4, -26, 14, 26);
            // Attack limb
            if (f.state !== 'idle') {
                ctx.fillStyle = '#fbbf24';
                ctx.fillRect(f.dir > 0 ? 20 : -68, -68, 48, 14);
            }
            ctx.font = '700 13px "Outfit", sans-serif';
            ctx.fillStyle = '#ffffff';
            ctx.textAlign = 'center';
            ctx.fillText(name, 0, -118);
            ctx.restore();
        };

        drawFighter(ms.player, '#38bdf8', 'YOU (IRON FIST)', true);
        drawFighter(ms.rival, '#f43f5e', `${ms.rival.name} (${Math.ceil(ms.rival.hp)} HP)`, false);

        for (const b of this.projectiles) {
            ctx.fillStyle = b.color;
            ctx.beginPath();
            ctx.arc(b.x, b.y, b.r, 0, Math.PI * 2);
            ctx.fill();
        }
    }

    renderAdventure(ctx) {
        const ms = this.modeState;
        ctx.fillStyle = '#131624';
        ctx.fillRect(0, 0, this.width, this.height);

        // Dungeon tiles
        ctx.strokeStyle = 'rgba(148, 163, 184, 0.08)';
        for (let x = 0; x < this.width; x += 60) {
            ctx.strokeRect(x, 0, 60, this.height);
        }

        // Pickups
        for (const p of this.pickups) {
            ctx.fillStyle = p.type === 'chest' ? '#fbbf24' : '#38bdf8';
            ctx.beginPath();
            ctx.arc(p.x, p.y, p.type === 'chest' ? 11 : 6, 0, Math.PI * 2);
            ctx.fill();
        }

        // Slash ring
        if (ms.slashVisualTimer > 0) {
            ctx.strokeStyle = 'rgba(251, 191, 36, 0.85)';
            ctx.lineWidth = 6;
            ctx.beginPath();
            ctx.arc(ms.px, ms.py, ms.slashRadius, 0, Math.PI * 2);
            ctx.stroke();
        }

        // Orbiting Fireballs
        for (let o = 0; o < ms.orbCount; o++) {
            const oa = ms.orbAngle + (o * Math.PI * 2) / ms.orbCount;
            ctx.fillStyle = '#f97316';
            ctx.beginPath();
            ctx.arc(ms.px + Math.cos(oa) * 68, ms.py + Math.sin(oa) * 68, 10, 0, Math.PI * 2);
            ctx.fill();
        }

        // Hero
        ctx.fillStyle = '#38bdf8';
        ctx.beginPath();
        ctx.arc(ms.px, ms.py, 18, 0, Math.PI * 2);
        ctx.fill();

        // Monsters
        for (const e of this.entities) {
            ctx.fillStyle = e.color;
            ctx.beginPath();
            ctx.arc(e.x, e.y, e.r, 0, Math.PI * 2);
            ctx.fill();
        }
    }

    renderZombie(ctx) {
        const ms = this.modeState;
        ctx.fillStyle = '#060b0e';
        ctx.fillRect(0, 0, this.width, this.height);

        // Tactical Flashlight Cone
        const aimAngle = Math.atan2(this.mouse.y - ms.py, this.mouse.x - ms.px);
        ctx.save();
        ctx.fillStyle = 'rgba(56, 189, 248, 0.13)';
        ctx.beginPath();
        ctx.moveTo(ms.px, ms.py);
        ctx.arc(ms.px, ms.py, 420, aimAngle - 0.48, aimAngle + 0.48);
        ctx.closePath();
        ctx.fill();
        ctx.restore();

        for (const p of this.pickups) {
            ctx.fillStyle = '#22c55e';
            ctx.fillRect(p.x - 10, p.y - 10, 20, 20);
        }

        for (const b of this.projectiles) {
            ctx.fillStyle = '#fde047';
            ctx.beginPath();
            ctx.arc(b.x, b.y, 4, 0, Math.PI * 2);
            ctx.fill();
        }

        for (const z of this.entities) {
            ctx.fillStyle = z.boomer ? '#22c55e' : '#84cc16';
            ctx.beginPath();
            ctx.arc(z.x, z.y, z.r, 0, Math.PI * 2);
            ctx.fill();
        }

        ctx.fillStyle = '#38bdf8';
        ctx.beginPath();
        ctx.arc(ms.px, ms.py, 16, 0, Math.PI * 2);
        ctx.fill();
    }

    renderHoops(ctx) {
        const ms = this.modeState;
        ctx.fillStyle = '#0f172a';
        ctx.fillRect(0, 0, this.width, this.height);
        ctx.fillStyle = '#1e293b';
        ctx.fillRect(0, 425, this.width, 115);

        // Hoop & Backboard
        ctx.fillStyle = '#e2e8f0';
        ctx.fillRect(ms.hoopX + 26, 155, 10, 95);
        ctx.fillRect(ms.hoopX + 32, 250, 14, 185);
        ctx.strokeStyle = '#f97316';
        ctx.lineWidth = 5;
        ctx.beginPath();
        ctx.moveTo(ms.hoopX - 22, ms.hoopY);
        ctx.lineTo(ms.hoopX + 26, ms.hoopY);
        ctx.stroke();

        // Player & Defender
        ctx.fillStyle = '#38bdf8';
        ctx.fillRect(ms.playerX - 16, ms.playerY - 68, 32, 68);
        ctx.fillStyle = '#ef4444';
        ctx.fillRect(ms.defenderX - 16, ms.defenderY - 72, 32, 72);

        // Power Meter when charging
        if (ms.charging) {
            ctx.fillStyle = '#0f172a';
            ctx.fillRect(ms.playerX - 28, ms.playerY - 95, 56, 12);
            ctx.fillStyle = Math.abs(ms.power - 80) <= 10 ? '#22c55e' : '#f97316';
            ctx.fillRect(ms.playerX - 28, ms.playerY - 95, (ms.power / 100) * 56, 12);
        }

        for (const b of ms.balls) {
            ctx.fillStyle = ms.streak >= 3 ? '#ef4444' : '#f97316';
            ctx.beginPath();
            ctx.arc(b.x, b.y, 13, 0, Math.PI * 2);
            ctx.fill();
        }
    }

    renderMoto(ctx) {
        const ms = this.modeState;
        ctx.fillStyle = '#0c0a1d';
        ctx.fillRect(0, 0, this.width, this.height);

        // Terrain polygon
        ctx.fillStyle = '#312e81';
        ctx.beginPath();
        ctx.moveTo(0, this.height);
        for (let x = 0; x <= this.width; x += 20) {
            ctx.lineTo(x, this.terrainHeightAt(ms.scrollX + x));
        }
        ctx.lineTo(this.width, this.height);
        ctx.closePath();
        ctx.fill();

        // Stunt Bike
        ctx.save();
        ctx.translate(ms.bikeX, ms.bikeY - 14);
        ctx.rotate(ms.angle);
        ctx.fillStyle = '#0f172a';
        ctx.beginPath();
        ctx.arc(-22, 12, 14, 0, Math.PI * 2);
        ctx.arc(22, 12, 14, 0, Math.PI * 2);
        ctx.fill();
        ctx.fillStyle = '#f97316';
        ctx.fillRect(-20, -10, 40, 14);
        ctx.restore();
    }

    renderSpace(ctx) {
        const ms = this.modeState;
        ctx.fillStyle = '#050814';
        ctx.fillRect(0, 0, this.width, this.height);

        ctx.fillStyle = '#ffffff';
        for (const s of this.stars) {
            s.x -= s.z * 2.5;
            if (s.x < 0) s.x = this.width;
            ctx.fillRect(s.x, s.y, s.z, s.z);
        }

        // Player Starfighter
        ctx.fillStyle = '#38bdf8';
        ctx.beginPath();
        ctx.moveTo(ms.shipX + 30, ms.shipY);
        ctx.lineTo(ms.shipX - 22, ms.shipY - 20);
        ctx.lineTo(ms.shipX - 12, ms.shipY);
        ctx.lineTo(ms.shipX - 22, ms.shipY + 20);
        ctx.closePath();
        ctx.fill();

        for (const b of this.projectiles) {
            ctx.fillStyle = '#38bdf8';
            ctx.fillRect(b.x - 8, b.y - 2, 16, 4);
        }

        for (const e of this.entities) {
            ctx.fillStyle = e.boss ? '#a855f7' : '#f43f5e';
            ctx.beginPath();
            ctx.arc(e.x, e.y, e.r, 0, Math.PI * 2);
            ctx.fill();
        }
    }

    renderCrownRoyale(ctx) {
        const ms = this.modeState;
        ctx.fillStyle = '#0b0f1d';
        ctx.fillRect(0, 0, this.width, this.height);

        ctx.fillStyle = '#1e293b';
        ctx.fillRect(0, 485, this.width, 55);
        for (const plat of ms.platforms) {
            ctx.fillStyle = '#4f46e5';
            ctx.fillRect(plat.x, plat.y, plat.w, plat.h);
        }

        if (ms.empVisual > 0) {
            ctx.strokeStyle = '#38bdf8';
            ctx.lineWidth = 4;
            ctx.beginPath();
            ctx.arc(ms.racers[0].x, ms.racers[0].y, 165 * (1 - ms.empVisual * 2), 0, Math.PI * 2);
            ctx.stroke();
        }

        ms.racers.forEach((r, idx) => {
            ctx.fillStyle = r.color;
            ctx.beginPath();
            ctx.roundRect(r.x - 18, r.y - 36, 36, 36, 8);
            ctx.fill();

            if (ms.crownHolder === idx) {
                ctx.fillStyle = '#fbbf24';
                ctx.beginPath();
                ctx.arc(r.x, r.y - 48, 10, 0, Math.PI * 2);
                ctx.fill();
            }

            ctx.font = '700 11px "Outfit", sans-serif';
            ctx.fillStyle = '#ffffff';
            ctx.textAlign = 'center';
            ctx.fillText(`${r.name} (${Math.round(r.pts)})`, r.x, r.y - 62);
        });
    }

    renderPrismBreaker(ctx) {
        const ms = this.modeState;
        ctx.fillStyle = '#090d1a';
        ctx.fillRect(0, 0, this.width, this.height);

        for (const br of ms.bricks) {
            ctx.fillStyle = br.color;
            ctx.beginPath();
            ctx.roundRect(br.x, br.y, br.w, br.h, 5);
            ctx.fill();
        }

        for (const l of this.projectiles) {
            ctx.fillStyle = '#38bdf8';
            ctx.fillRect(l.x - 2, l.y - 10, 4, 18);
        }

        ctx.fillStyle = '#38bdf8';
        ctx.beginPath();
        ctx.roundRect(ms.paddleX, this.height - 48, ms.paddleW, 16, 8);
        ctx.fill();

        for (const b of ms.balls) {
            ctx.fillStyle = '#fbbf24';
            ctx.beginPath();
            ctx.arc(b.x, b.y, b.r, 0, Math.PI * 2);
            ctx.fill();
        }
    }

    renderHud(ctx) {
        // Top Translucent Tactical Bar
        ctx.fillStyle = 'rgba(9, 13, 24, 0.82)';
        ctx.fillRect(0, 0, this.width, 54);
        ctx.fillStyle = 'rgba(249, 115, 22, 0.35)';
        ctx.fillRect(0, 53, this.width, 1);

        ctx.font = '800 16px "Outfit", sans-serif';
        ctx.fillStyle = '#ffffff';
        ctx.textAlign = 'left';
        ctx.fillText(`SCORE: ${this.score.toLocaleString()}`, 20, 33);

        ctx.fillStyle = '#fbbf24';
        ctx.fillText(`BEST: ${this.highScore.toLocaleString()}`, 190, 33);

        ctx.fillStyle = '#38bdf8';
        ctx.fillText(`LVL ${this.level}`, 355, 33);

        if (this.combo > 1) {
            ctx.fillStyle = '#f97316';
            ctx.fillText(`${this.combo}x COMBO`, 440, 33);
        }

        // Health & Shield Bars
        ctx.fillStyle = '#1e293b';
        ctx.fillRect(this.width - 330, 14, 140, 12);
        ctx.fillStyle = this.health > 35 ? '#22c55e' : '#ef4444';
        ctx.fillRect(this.width - 330, 14, 140 * (this.health / 100), 12);

        ctx.fillStyle = '#1e293b';
        ctx.fillRect(this.width - 330, 30, 140, 8);
        ctx.fillStyle = '#38bdf8';
        ctx.fillRect(this.width - 330, 30, 140 * (this.shield / 100), 8);

        ctx.font = '700 13px "Outfit", sans-serif';
        ctx.fillStyle = '#e2e8f0';
        ctx.textAlign = 'right';
        ctx.fillText(`AMMO / NRG: ${Math.round(this.ammo)}`, this.width - 20, 33);

        if (this.bannerTimer > 0 && this.statusBanner) {
            ctx.fillStyle = 'rgba(249, 115, 22, 0.92)';
            ctx.fillRect(this.width / 2 - 220, 68, 440, 34);
            ctx.font = '800 15px "Outfit", sans-serif';
            ctx.fillStyle = '#ffffff';
            ctx.textAlign = 'center';
            ctx.fillText(this.statusBanner, this.width / 2, 90);
        }
    }

    renderOverlay(ctx) {
        ctx.fillStyle = 'rgba(6, 9, 19, 0.84)';
        ctx.fillRect(0, 0, this.width, this.height);

        ctx.textAlign = 'center';
        ctx.font = '900 42px "Outfit", sans-serif';
        ctx.fillStyle = '#ffffff';

        if (this.state === 'start') {
            ctx.fillText(this.gameName.toUpperCase(), this.width / 2, this.height / 2 - 55);
            ctx.font = '600 17px "Inter", sans-serif';
            ctx.fillStyle = '#94a3b8';
            ctx.fillText('60FPS Interactive Action Engine • Web Audio SFX • Live Leaderboard Sync', this.width / 2, this.height / 2 - 15);

            ctx.fillStyle = '#f97316';
            ctx.beginPath();
            ctx.roundRect(this.width / 2 - 135, this.height / 2 + 20, 270, 56, 14);
            ctx.fill();
            ctx.font = '800 20px "Outfit", sans-serif';
            ctx.fillStyle = '#ffffff';
            ctx.fillText('▶ CLICK TO START GAME', this.width / 2, this.height / 2 + 55);
        } else if (this.state === 'paused') {
            ctx.fillText('GAME PAUSED', this.width / 2, this.height / 2 - 20);
            ctx.font = '600 18px "Inter", sans-serif';
            ctx.fillStyle = '#fbbf24';
            ctx.fillText('Click inside arena or press [P] to Resume', this.width / 2, this.height / 2 + 25);
        } else if (this.state === 'gameover') {
            ctx.fillStyle = '#f43f5e';
            ctx.fillText('MISSION COMPLETE / GAME OVER', this.width / 2, this.height / 2 - 65);
            ctx.font = '800 26px "Outfit", sans-serif';
            ctx.fillStyle = '#fbbf24';
            ctx.fillText(`FINAL SCORE: ${this.score.toLocaleString()} PTS  •  LEVEL ${this.level}`, this.width / 2, this.height / 2 - 15);

            ctx.fillStyle = '#f97316';
            ctx.beginPath();
            ctx.roundRect(this.width / 2 - 135, this.height / 2 + 22, 270, 54, 14);
            ctx.fill();
            ctx.font = '800 19px "Outfit", sans-serif';
            ctx.fillStyle = '#ffffff';
            ctx.fillText('↻ PLAY AGAIN [R]', this.width / 2, this.height / 2 + 56);
        }
    }
}

window.NexusArcadeEngine = {
    mount(config) {
        return new NexusArcadeInstance(config);
    },
};
