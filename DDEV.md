# DDEV Setup Guide for Quill Project

## ✅ DDEV Configuration Complete

Your Laravel project is now running with DDEV and MySQL 8.0!

---

## 🚀 Quick Start

### Start the Project
```bash
ddev start
```

### Access the Application
- **Web:** https://quill.ddev.site
- **Database:** Host: `db`, Port: `3306`, Database: `db`, User: `db`, Password: `db`

### Stop the Project
```bash
ddev stop
```

### Restart the Project
```bash
ddev restart
```

---

## 📋 Common Commands

### Laravel Artisan
```bash
ddev artisan migrate
ddev artisan make:controller UserController
ddev artisan tinker
ddev artisan route:list
ddev artisan migrate:fresh --seed
```

### NPM/Node
```bash
ddev npm install
ddev npm run dev
ddev npm run build
ddev npm run watch
```

### Composer
```bash
ddev composer install
ddev composer require package/name
ddev composer update
```

### Database Operations
```bash
# Access MySQL CLI
ddev mysql

# Export database
ddev export-db --file=backup.sql.gz

# Import database
ddev import-db --file=backup.sql.gz

# View database info
ddev describe
```

### SSH into Container
```bash
ddev ssh
```

---

## 🔧 DDEV Configuration

### PHP Version
- **Version:** 8.4
- **Location:** `.ddev/config.yaml`

### Database
- **Type:** MySQL 8.0
- **Connection Details:**
  - Host: `db`
  - Port: `3306`
  - Database: `db`
  - Username: `db`
  - Password: `db`

### Web Server
- **Type:** nginx-fpm
- **Document Root:** `public/`

---

## 🛠️ Custom DDEV Commands

The following custom commands have been added:

1. **`ddev artisan`** - Run Laravel Artisan commands
2. **`ddev npm`** - Run npm commands
3. **`ddev composer`** - Run Composer commands (built-in, enhanced)

---

## 📦 Development Workflow

### 1. Start Development Environment
```bash
# Start DDEV
ddev start

# In a separate terminal, start Vite dev server
ddev npm run dev
```

### 2. Access Your Application
- Open https://quill.ddev.site in your browser
- Hot module replacement (HMR) will work with Vite

### 3. Run Migrations (if needed)
```bash
ddev artisan migrate
```

### 4. Create a User
```bash
# Via Tinker
ddev artisan tinker
>>> $org = App\Models\Organization::create(['name' => 'Test Org']);
>>> $user = App\Models\User::create([
...   'name' => 'Admin',
...   'email' => 'admin@example.com',
...   'password' => bcrypt('password'),
...   'organization_id' => $org->id
... ]);

# Or register via the UI at https://quill.ddev.site/register
```

---

## 🗃️ Database Management

### PHPMyAdmin (optional)
```bash
ddev get ddev/ddev-phpmyadmin
ddev restart
```

Access at: https://quill.ddev.site:8037

### TablePlus / DataGrip / Sequel Pro
Use these connection details:
- **Host:** 127.0.0.1
- **Port:** (run `ddev describe` to get the port)
- **Database:** db
- **User:** db
- **Password:** db

---

## 🔍 Troubleshooting

### Port Conflicts
If you see "Port 80 is busy":
- DDEV will automatically use an alternate port
- Check the actual URL with: `ddev describe`

### Clear All Caches
```bash
ddev artisan cache:clear
ddev artisan config:clear
ddev artisan route:clear
ddev artisan view:clear
```

### Rebuild Containers
```bash
ddev restart
# or for a complete rebuild
ddev delete --omit-snapshot
ddev start
```

### View Logs
```bash
ddev logs
ddev logs -f  # follow logs
```

### Check PHP Version
```bash
ddev php -v
```

---

## 📂 File Structure

```
.ddev/
├── config.yaml                    # Main DDEV configuration
├── commands/
│   └── web/
│       ├── artisan               # Custom artisan command
│       └── npm                   # Custom npm command
└── web-build/
    └── post-start.sh             # Post-start hooks
```

---

## 🌐 Environment Variables

The `.env` file has been configured for DDEV:

```env
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=db
DB_USERNAME=db
DB_PASSWORD=db
```

**Note:** Don't commit `.env` file to version control!

---

## 🔐 HTTPS/SSL

DDEV automatically provides HTTPS with self-signed certificates.

To trust the certificate:
```bash
mkcert -install  # One-time setup
ddev restart
```

---

## 📝 Additional Resources

- [DDEV Documentation](https://ddev.readthedocs.io/)
- [DDEV Laravel Quickstart](https://ddev.readthedocs.io/en/stable/users/quickstart/#laravel)
- [DDEV Commands](https://ddev.readthedocs.io/en/stable/users/usage/commands/)

---

## 🚦 Status Check

Run this to verify everything is working:

```bash
ddev describe
```

You should see:
- ✅ Status: running
- ✅ URLs accessible
- ✅ Database connected

---

## 🎯 Next Steps

1. Visit https://quill.ddev.site/register to create your first account
2. Start building features from `tasks.md`
3. Run `ddev npm run dev` for frontend development

Happy coding! 🎉
