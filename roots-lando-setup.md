# Roots.io Stack — Lando Dev Environment Build Prompt

## Project Goal

Build a complete local WordPress development environment using the full Roots.io stack contained in Lando/Docker. This is a learning-oriented reference build using the latest stable versions of all components.

**Stack:**
- **Lando** — local dev environment / Docker orchestration
- **Trellis** — Ansible-based server provisioning and environment config
- **Bedrock** — WordPress boilerplate with Composer dependency management
- **Acorn** — Laravel framework integration layer for WordPress
- **Sage** — WordPress starter theme using Laravel Blade + Tailwind CSS

---

## Prerequisites

Before starting, confirm the following are installed on the host machine:

- [ ] Docker Desktop (or Docker Engine + Docker Compose)
- [ ] Lando (latest stable) — https://lando.dev
- [ ] Node.js (LTS) and npm
- [ ] Composer (latest stable)
- [ ] Git

> **Checkpoint:** Run `lando version`, `composer --version`, and `node --version` to confirm all are available before proceeding.

---

## VPS + Tailscale Network Configuration

> **Required if running Claude Code and Lando on a remote VPS accessed via Tailscale.**

By default, Lando's Traefik proxy binds strictly to `127.0.0.1` (ports **80** and **443**), making it unreachable from any external interface — including your Tailscale IP. You need to override the `bindAddress` in Lando's global config before starting any project.

### Set bindAddress globally

Edit (or create) `~/.lando/config.yml` on the VPS:

```yaml
bindAddress: "0.0.0.0"
```

This tells Lando's Traefik proxy to listen on all interfaces, including the Tailscale network adapter (`100.x.x.x`), so you can reach the site from any machine on your Tailscale network.

> **Security note:** `0.0.0.0` binds to all interfaces on the VPS. Since Tailscale handles its own access control (ACLs), this is generally safe in a VPS-only context, but make sure your VPS firewall (ufw, iptables, etc.) is not leaving ports 80/443 open to the public internet unless you intend that. You can verify with `sudo ufw status` or `sudo ss -tlnp | grep -E ':80|:443'`.

### Port reference

| Protocol | Default Port | Fallback Ports (if 80/443 are taken) |
|----------|-------------|---------------------------------------|
| HTTP     | 80          | 8000, 8080, 8888, 8008               |
| HTTPS    | 443         | 444, 4433, 4444, 4443                |

If something on your VPS is already using port 80 or 443 (e.g. nginx, Apache), Lando will fall back automatically. You can also pin specific ports in `~/.lando/config.yml`:

```yaml
bindAddress: "0.0.0.0"
proxyHttpPort: 8080
proxyHttpsPort: 4433
```

### Update WP_HOME to use your Tailscale IP

If you're accessing the site via Tailscale IP rather than a domain, update `site/.env` accordingly:

```env
WP_HOME=http://100.x.x.x        # HTTP, no custom domain
# or
WP_HOME=http://100.x.x.x:8080   # if Lando fell back to a non-standard port
WP_SITEURL=${WP_HOME}/wp
```

You can also proxy a friendly local domain through your Tailscale node if you have a custom domain configured in Tailscale DNS.

### Lando .lando.yml proxy block

Update the proxy section to use your VPS hostname or Tailscale hostname (if you've set one in the Tailscale admin console):

```yaml
proxy:
  appserver:
    - my-project.lndo.site        # works if DNS resolves to 127.0.0.1 locally
    # OR use your Tailscale machine name if configured in Tailscale DNS:
    # - my-project.my-tailnet.ts.net
```

> **Checkpoint:** After updating `~/.lando/config.yml`, run `lando poweroff && lando start` to restart the proxy with the new bind address. Then from another machine on your Tailscale network, run `curl -I http://100.x.x.x` (substituting your VPS Tailscale IP) — you should get an HTTP response from Traefik.

---

## Phase 1 — Project Scaffold

### 1.1 Create the project directory structure

Roots.io expects Trellis and Bedrock to sit as siblings inside a parent project folder:

```
my-project/
├── trellis/       ← Ansible provisioning
└── site/          ← Bedrock WordPress root
```

Create the parent directory and initialize git:

```bash
mkdir my-project && cd my-project
git init
```

### 1.2 Install Trellis CLI and create the Trellis project

```bash
# Install Trellis CLI globally if not already installed
composer global require roots/trellis-cli

# Scaffold Trellis inside the project root
trellis new --name my-project .
```

This will scaffold both `trellis/` and `site/` (Bedrock) directories automatically.

> **Checkpoint:** Confirm `trellis/` and `site/` directories exist with expected contents. `site/` should contain a `composer.json` referencing `roots/wordpress` and `roots/bedrock`.

---

## Phase 2 — Bedrock Configuration

### 2.1 Install Bedrock dependencies

```bash
cd site
composer install
```

### 2.2 Configure the `.env` file

Copy the example env and configure for local development:

```bash
cp .env.example .env
```

Edit `.env` with the following values (these will align with Lando's DB settings in Phase 3):

```env
DB_NAME=wordpress
DB_USER=wordpress
DB_PASSWORD=wordpress
DB_HOST=database

WP_ENV=development
WP_HOME=https://my-project.lndo.site
WP_SITEURL=${WP_HOME}/wp
```

> **Checkpoint:** Confirm `web/wp/` directory exists (Bedrock installs WordPress as a Composer package here) and `.env` is populated correctly.

---

## Phase 3 — Lando Configuration

### 3.1 Create `.lando.yml` in the project root

Create `/my-project/.lando.yml` with the following configuration:

```yaml
name: my-project
recipe: wordpress
config:
  webroot: site/web
  php: '8.3'
  via: nginx
  database: mariadb:10.6
  xdebug: false

services:
  appserver:
    build_as_root:
      - apt-get update && apt-get install -y git unzip
    overrides:
      environment:
        COMPOSER_ALLOW_SUPERUSER: 1

  database:
    type: mariadb:10.6
    creds:
      user: wordpress
      password: wordpress
      database: wordpress

tooling:
  composer:
    service: appserver
    cmd: composer
  wp:
    service: appserver
    cmd: wp --allow-root
  npm:
    service: node
    cmd: npm

proxy:
  appserver:
    - my-project.lndo.site
```

### 3.2 Start Lando

```bash
cd my-project
lando start
```

> **Checkpoint:** Lando should build and start without errors. Visit `https://my-project.lndo.site` — you should see a WordPress install screen or Bedrock error (expected before WP is installed). Run `lando info` to confirm all services are healthy.

### 3.3 Install WordPress via WP-CLI

```bash
lando wp core install \
  --url="https://my-project.lndo.site" \
  --title="My Project" \
  --admin_user="admin" \
  --admin_password="password" \
  --admin_email="admin@example.com"
```

> **Checkpoint:** Visit `https://my-project.lndo.site/wp/wp-admin` and confirm login works.

---

## Phase 4 — Install and Configure Sage

### 4.1 Create the Sage theme via Composer

```bash
cd site
composer create-project roots/sage web/app/themes/my-theme
```

During the Sage install prompt, select:
- **CSS Framework:** Tailwind CSS
- **Build tool:** Bud (default)

### 4.2 Install Acorn

Acorn is required for Sage's Blade templating to function. Install it as a Bedrock dependency:

```bash
composer require roots/acorn
```

Then activate it by adding the service provider. Bedrock auto-discovers it via Composer, but confirm by running:

```bash
lando wp acorn about
```

> **Checkpoint:** `lando wp acorn about` should return Acorn version info without errors. If it fails, check that `Roots\Acorn\ServiceProvider` is discoverable.

### 4.3 Install theme Node dependencies and build assets

```bash
cd web/app/themes/my-theme
npm install
npm run build
```

> **Checkpoint:** A `public/` directory should now exist inside the theme folder containing compiled CSS and JS assets.

### 4.4 Activate the theme

```bash
lando wp theme activate my-theme
```

> **Checkpoint:** Visit the front end at `https://my-project.lndo.site` — the default Sage theme should now be rendering.

---

## Phase 5 — Build the Template Files

All templates live in `site/web/app/themes/my-theme/resources/views/`. Sage uses Laravel Blade with a `.blade.php` extension.

### 5.1 Layouts

**`resources/views/layouts/app.blade.php`** — the master layout wrapper:

```blade
<!DOCTYPE html>
<html {!! get_language_attributes() !!}>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php(wp_head())
  </head>
  <body @php(body_class())>
    @php(wp_body_open())

    <div id="app">
      @include('partials.header')

      <main id="main" class="main">
        @yield('content')
      </main>

      @include('partials.footer')
    </div>

    @php(wp_footer())
  </body>
</html>
```

### 5.2 Partials

**`resources/views/partials/header.blade.php`:**

```blade
<header class="site-header bg-white shadow-sm py-4">
  <div class="container mx-auto px-4 flex items-center justify-between">
    <a href="{{ home_url('/') }}" class="site-logo text-xl font-bold text-gray-900">
      {!! get_bloginfo('name') !!}
    </a>
    <nav class="site-nav">
      {!! wp_nav_menu([
        'theme_location' => 'primary_navigation',
        'menu_class'     => 'flex gap-6',
        'container'      => false,
        'echo'           => false,
      ]) !!}
    </nav>
  </div>
</header>
```

**`resources/views/partials/footer.blade.php`:**

```blade
<footer class="site-footer bg-gray-900 text-gray-400 py-8 mt-16">
  <div class="container mx-auto px-4 text-center text-sm">
    <p>&copy; {{ date('Y') }} {!! get_bloginfo('name') !!}. All rights reserved.</p>
  </div>
</footer>
```

### 5.3 Homepage Template

**`resources/views/front-page.blade.php`:**

```blade
@extends('layouts.app')

@section('content')
  <section class="hero bg-gray-50 py-24 text-center">
    <div class="container mx-auto px-4">
      <h1 class="text-5xl font-bold text-gray-900 mb-6">
        Welcome to {!! get_bloginfo('name') !!}
      </h1>
      <p class="text-xl text-gray-600 max-w-2xl mx-auto">
        {!! get_bloginfo('description') !!}
      </p>
    </div>
  </section>

  <section class="content py-16">
    <div class="container mx-auto px-4 max-w-3xl">
      @php(the_content())
    </div>
  </section>
@endsection
```

### 5.4 Inner Page Template

**`resources/views/page.blade.php`:**

```blade
@extends('layouts.app')

@section('content')
  <div class="container mx-auto px-4 py-16 max-w-3xl">
    @while(have_posts()) @php(the_post())
      <article @php(post_class('prose prose-lg max-w-none'))>
        <h1 class="text-4xl font-bold text-gray-900 mb-8">
          {!! get_the_title() !!}
        </h1>
        <div class="entry-content">
          @php(the_content())
        </div>
      </article>
    @endwhile
  </div>
@endsection
```

### 5.5 404 Page

**`resources/views/404.blade.php`:**

```blade
@extends('layouts.app')

@section('content')
  <div class="container mx-auto px-4 py-32 text-center">
    <h1 class="text-8xl font-bold text-gray-200 mb-4">404</h1>
    <h2 class="text-3xl font-semibold text-gray-800 mb-4">Page Not Found</h2>
    <p class="text-gray-500 mb-8">
      Sorry, the page you're looking for doesn't exist or has been moved.
    </p>
    <a href="{{ home_url('/') }}"
       class="inline-block bg-gray-900 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition">
      Back to Home
    </a>
  </div>
@endsection
```

> **Checkpoint:** After creating each template, run `npm run build` inside the theme directory and reload the relevant URL to confirm each template renders without Blade errors. Check the browser console and WP debug log at `site/web/app/debug.log`.

---

## Phase 6 — Register Navigation Menu

In `site/web/app/themes/my-theme/app/setup.php`, confirm or add the primary nav registration inside the `after_setup_theme` hook:

```php
register_nav_menus([
    'primary_navigation' => __('Primary Navigation', 'my-theme'),
]);
```

Then create and assign a menu in **WP Admin → Appearance → Menus**, assigning it to the "Primary Navigation" location.

> **Checkpoint:** The header nav should now render the assigned menu items.

---

## Phase 7 — Final Build & Verification

```bash
# Inside theme directory
npm run build

# Confirm WP and Acorn are healthy
lando wp core version
lando wp acorn about

# Confirm theme is active
lando wp theme list
```

**Final checklist:**

- [ ] Homepage renders with header, hero section, and footer
- [ ] An inner page renders with correct layout
- [ ] Navigating to a non-existent URL shows the 404 template
- [ ] Primary navigation menu is visible in the header
- [ ] No PHP or Blade errors in the debug log
- [ ] `npm run build` completes without errors
- [ ] Lando services all show as `RUNNING` in `lando info`

---

## Notes & Next Steps

- **Trellis** is configured in this build but not used for provisioning a remote server — that's a separate workflow involving vault-encrypted secrets and Ansible playbooks. Review `trellis/group_vars/` when ready to deploy.
- **Bud** (the Sage build tool) supports HMR for local dev — run `npm run dev` instead of `npm run build` during active development.
- **Acorn** enables using Laravel features like service providers, config files, and view composers inside WordPress — explore `app/Providers/` in the theme for extension points.
- To add block editor (Gutenberg) support with Blade, look into the `roots/acorn-view` package as a next step.
