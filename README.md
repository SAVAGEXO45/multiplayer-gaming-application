# NEXUS ARENA — Competitive Multiplayer Gaming Platform

Nexus Arena is a production-quality, full-stack multiplayer gaming platform built with **Laravel 13**, **PHP 8.5+**, **Laravel Reverb WebSockets**, **Laravel Sanctum**, **Tailwind CSS**, and **Alpine.js**.

---

## 1. Technology Stack & Architecture

- **Backend Framework**: Laravel 13 (`v13.42.0`) on PHP 8.5+
- **Database**: MySQL 8 & SQLite compatible schema with foreign keys, composite indexes, unique constraints, and transactional locking (`lockForUpdate()`)
- **Authentication & Authorization**:
  - Session-based Web Authentication + **Laravel Sanctum** API Token Authentication
  - Role-based access control (`admin` and `player`) enforced via `AdminMiddleware`, `EnsureAccountIsActive`, and `GameRoomPolicy`
- **Real-Time Communication**:
  - **Laravel Reverb** + **Laravel Echo** (`PresenceChannel` for game rooms, `PrivateChannel` for player notifications)
  - Automatic fallback state synchronization so real-time rooms remain responsive even if the WebSocket daemon is not running
- **Event-Driven & Queued Processing**:
  - `MatchCompleted` event & `ProcessMatchResultListener`
  - `RecalculateLeaderboardsJob` background queue job
- **10 Playable 60FPS Action Games & Arcade Engine**:
  - `Cyber Strike: Neo FPS` — 2.5D Raycasting first-person shooter with live minimap, patrolling cyborg soldiers, health packs, muzzle flashes, and dynamic weapon recoil.
  - `Nitro Apex GT` — High-speed pseudo-3D curve racing simulator with acceleration, drifting, nitro boost, roadside billboards, and oncoming traffic.
  - `Shadow Dojo Brawl` — 2D side-view martial arts combat with combos, flying kicks, blocking, energy meters, and ninja AI.
  - `Emberfall Survivors` — Top-down vampire-survivors arena with auto-firing plasma orbs, experience gems, level-up choices, and enemy swarms.
  - `Deadzone Outbreak` — Twin-stick zombie survival shooter with 360-degree aiming, barricades, ammo pickups, and night-vision flashlights.
  - `Street Hoops Pro` — Physics-based basketball arcade with parabolic trajectory arcs, backboard bounces, swish mechanics, and shot-clock pressure.
  - `Moto Xtreme Trials` — 2D side-scrolling physics stunt trials with real-time balance tilt, shocks, suspension, loop-the-loops, and backflip bonuses.
  - `Nova Vanguard 3D` — 3D deep-space starfighter combat with enemy cruisers, homing photon torpedoes, warp drives, and cockpit HUD.
  - `Arena Tag Royale` — Top-down competitive multiplayer tag with speed pads, dash cooldowns, bouncing walls, and sudden death timers.
  - `Prism Breaker Overdrive` — Arcade brick-breaker with multiball, laser paddles, plasma fireballs, falling power-ups, and explosive score chains.
- **Server-Authoritative Competitive Engines & Rooms**:
  - `ActionArenaEngine` — Real-time high-score match submission, session telemetry, score verification, and rank points calculation.
  - `TicTacToeEngine`, `RockPaperScissorsEngine`, `ConnectFourEngine`, `ChessEngine` — Turn enforcement, simultaneous choice locking, gravity matrix, and tactical legal movement validation.
- **Synthesized Web Audio SFX**:
  - Zero-latency procedural sound synthesizer (laser blasts, engine roars, martial punch impacts, crowd cheers, coin pickups, power-ups, game over stingers). No external audio assets needed!

---

## 2. Demo Accounts (Seeded out of the box)

| Role | Username | Email | Password |
| :--- | :--- | :--- | :--- |
| **Admin** | `nexus_admin` | `admin@nexusarena.gg` | `password123` |
| **Player #1** | `cyber_viper` | `viper@nexusarena.gg` | `password123` |
| **Player #2** | `astral_nova` | `nova@nexusarena.gg` | `password123` |
| **Player #3** | `neon_valkyrie` | `valkyrie@nexusarena.gg` | `password123` |
| **Player #4** | `iron_titan` | `titan@nexusarena.gg` | `password123` |

> Tip: The Login page (`/login`) also includes one-click **"Fill Admin Demo"** and **"Fill Player #1 Demo"** buttons for rapid local testing.

---

## 3. Installation & Local Setup (Windows / Laravel Herd / VS Code)

1. **Navigate to the project directory**:
   ```powershell
   cd C:\Users\USER\.gemini\antigravity\scratch\nexus-arena
   ```

2. **Install PHP & Node dependencies**:
   ```powershell
   composer install
   npm install
   ```

3. **Environment Configuration**:
   - Copy `.env.example` to `.env` (already configured in this workspace).
   - **MySQL 8 Setup**: Create a `nexus_arena` database in MySQL 8 and uncomment the MySQL block in `.env`:
     ```env
     DB_CONNECTION=mysql
     DB_HOST=127.0.0.1
     DB_PORT=3306
     DB_DATABASE=nexus_arena
     DB_USERNAME=root
     DB_PASSWORD=your_mysql_password
     ```
   - **Zero-Config SQLite Setup**: Leave `DB_CONNECTION=sqlite` to use `database/database.sqlite` immediately.

4. **Run Migrations, Seed Demo Data & Link Storage**:
   ```powershell
   php artisan key:generate
   php artisan migrate:fresh --seed
   php artisan storage:link
   ```

5. **Compile Frontend Assets**:
   ```powershell
   npm run build
   ```

6. **Start the Application Servers**:
   - **Web Server** (or use Laravel Herd `http://nexus-arena.test`):
     ```powershell
     php artisan serve
     ```
   - **Laravel Reverb WebSocket Server**:
     ```powershell
     php artisan reverb:start
     ```
   - **Queue Worker**:
     ```powershell
     php artisan queue:listen
     ```

---

## 4. RESTful API Documentation (`/api/*`)

All protected API endpoints accept `Authorization: Bearer <sanctum_token>` and return consistent JSON payloads (`{ "success": true, "data": ... }`).

### Authentication
- `POST /api/auth/register` — Register a new player account & receive a Sanctum token
- `POST /api/auth/login` — Authenticate with `email` (or `username`) and `password`
- `GET /api/auth/me` — Fetch authenticated user profile & global statistics
- `POST /api/auth/logout` — Revoke current Sanctum token

### Games & Leaderboards
- `GET /api/games` — List all active multiplayer games and active room counts
- `GET /api/games/{slug}` — Inspect a game's rules, instructions, and open public rooms
- `GET /api/leaderboards?period=global|weekly|monthly&game={slug}` — Query ranked standings
- `GET /api/achievements` — List all platform achievements and unlock status

### Multiplayer Game Rooms & Real-Time Moves
- `GET /api/rooms` — List open public rooms
- `POST /api/rooms` — Create a new room (`game_slug`, `name`, `visibility`: `public|private`)
- `POST /api/rooms/join-code` — Join a room by 6-character `room_code`
- `GET /api/rooms/{room_code}` — Get room details, player slots, and active session board state
- `POST /api/rooms/{room_code}/join` — Join room slot
- `POST /api/rooms/{room_code}/leave` — Leave room (handles automatic host reassignment & forfeit)
- `POST /api/rooms/{room_code}/start` — Start or restart (`POST /api/rooms/{room_code}/restart`) a match session
- `POST /api/rooms/{room_code}/invite` — Invite a player by `username`
- `POST /api/rooms/{room_code}/moves` — Submit a server-validated move:
  - **Tic-Tac-Toe**: `{ "index": 0..8 }`
  - **Rock Paper Scissors**: `{ "choice": "rock" | "paper" | "scissors" }`
  - **Connect Four**: `{ "column": 0..6 }`
  - **Chess**: `{ "from_row": 6, "from_col": 4, "to_row": 4, "to_col": 4, "promotion": "Q" }`

### Friends, Users & Notifications
- `GET /api/users?q={search}` — Search active players
- `GET /api/users/{username}` — View player profile, game stats, and achievements
- `PUT /api/profile` — Update profile details and avatar
- `GET /api/friends` — List friends, incoming requests, and outgoing requests
- `POST /api/friends/requests` — Send friend request (`{ "username": "..." }`)
- `POST /api/friends/requests/{id}/accept` — Accept friend request
- `POST /api/friends/requests/{id}/reject` — Decline friend request
- `DELETE /api/friends/{user_id}` — Remove friend
- `GET /api/notifications` — List notifications & unread count
- `POST /api/notifications/{id}/read` — Mark notification as read
- `POST /api/notifications/read-all` — Mark all notifications as read

### Admin Management (`/api/admin/*` — Requires `admin` role)
- `GET /api/admin/overview` — Platform KPIs & telemetry
- `GET /api/admin/users` — List registered users
- `POST /api/admin/users/{user_id}/toggle-suspension` — Suspend or reactivate a player account
- `POST /api/admin/games/{slug}/toggle-status` — Enable or disable a game title
- `GET /api/admin/rooms` — Monitor active rooms
- `POST /api/admin/achievements` — Create a new achievement badge

---

## 5. Running Automated Tests

Run the full PHPUnit Unit & Feature test suite:
```powershell
php vendor/phpunit/phpunit/phpunit --testdox
```
