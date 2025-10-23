# 🌱 Database Seeder Setup

## ✅ Complete Seeder Configuration

---

## 📋 **Seeders Available**

### 1. **RolesAndPermissionsSeeder**
- Creates all 10 roles
- Creates all 41 permissions
- Assigns permissions to each role

### 2. **UserSeeder** ⭐ NEW
- Creates Super Admin user
- Creates test users (local environment only)

### 3. **ProjectSeeder**
- Creates sample project
- Creates project phases and tasks
- Assigns users to project

---

## 🚀 **Quick Start**

### Fresh Database Setup
```bash
# Reset database and run all seeders
php artisan migrate:fresh --seed

# Or run migrations and seeders separately
php artisan migrate:fresh
php artisan db:seed
```

### Run Specific Seeder
```bash
# Run only user seeder
php artisan db:seed --class=UserSeeder

# Run only roles and permissions
php artisan db:seed --class=RolesAndPermissionsSeeder

# Run only project seeder
php artisan db:seed --class=ProjectSeeder
```

---

## 👤 **Default Users Created**

### Production User (Always Created)
| Email | Password | Role | Access |
|-------|----------|------|--------|
| `admin@projectarchitect.com` | `password` | Super Admin | Full system access |

### Test Users (Local Environment Only)
| Email | Password | Role | Access |
|-------|----------|------|--------|
| `pm@projectarchitect.com` | `password` | Project Manager | Manage projects |
| `inspector@projectarchitect.com` | `password` | Inspector | Inspect tasks |
| `engineer@projectarchitect.com` | `password` | Engineer | Technical work |
| `worker@projectarchitect.com` | `password` | Worker | Complete tasks |
| `client@projectarchitect.com` | `password` | Client | View only |

---

## 🔐 **Login Credentials**

### Super Admin Login
```
Email: admin@projectarchitect.com
Password: password
```

⚠️ **IMPORTANT**: Change the default password in production!

---

## 📝 **Seeder Execution Order**

```
1. RolesAndPermissionsSeeder
   ↓ Creates roles and permissions first
   
2. UserSeeder
   ↓ Creates users and assigns roles
   
3. ProjectSeeder
   ↓ Creates projects and assigns users
```

This order is important because:
- Users need roles to exist before assignment
- Projects need users to exist before team assignment

---

## 🎯 **Testing Different Roles**

### Test Super Admin Features
```bash
# Login as: admin@projectarchitect.com
# Access:
- /settings/roles-permissions
- /settings/user-roles
- All other features
```

### Test Inspector Features
```bash
# Login as: inspector@projectarchitect.com
# Access:
- /inspector/dashboard
- Task inspections
- Material confirmations
```

### Test Worker Features
```bash
# Login as: worker@projectarchitect.com
# Access:
- /my-tasks (only their tasks)
- Simple task completion interface
```

### Test Client Features
```bash
# Login as: client@projectarchitect.com
# Access:
- View-only access to projects
- Cannot see materials, budgets, or team
```

---

## 🔧 **Customization**

### Change Default Credentials

Edit `/database/seeders/UserSeeder.php`:

```php
$superAdmin = User::firstOrCreate(
    ['email' => 'youremail@company.com'],  // Change email
    [
        'name' => 'Your Name',              // Change name
        'password' => Hash::make('your-secure-password'), // Change password
        'email_verified_at' => now(),
    ]
);
```

### Disable Test Users

In `UserSeeder.php`, remove or comment out the section:

```php
// Optionally create other test users
if (app()->environment('local')) {
    // ... test user creation code
}
```

### Add More Test Users

In `UserSeeder.php`, add more users:

```php
$director = User::firstOrCreate(
    ['email' => 'director@projectarchitect.com'],
    [
        'name' => 'Director Name',
        'password' => Hash::make('password'),
        'email_verified_at' => now(),
    ]
);
$director->assignRole('director');
```

---

## ⚠️ **Production Checklist**

Before deploying to production:

- [ ] Change super admin password
- [ ] Use strong, unique password
- [ ] Change super admin email to company email
- [ ] Remove or disable test user creation
- [ ] Set `APP_ENV=production` in `.env`
- [ ] Enable email verification if needed
- [ ] Review all permissions
- [ ] Test all roles thoroughly

---

## 🐛 **Troubleshooting**

### Error: "Role does not exist"
```bash
# Make sure roles are seeded first
php artisan db:seed --class=RolesAndPermissionsSeeder
php artisan db:seed --class=UserSeeder
```

### Error: "Duplicate entry"
```bash
# Users already exist, fresh seed or skip
php artisan migrate:fresh --seed
```

### Clear Permission Cache
```bash
php artisan cache:clear
php artisan config:clear
```

---

## 📊 **What Gets Seeded**

### Roles (10)
- super_admin
- project_manager
- contractor
- inspector
- engineer
- developer
- director
- manager
- client
- worker

### Permissions (41)
- Project permissions (4)
- Task permissions (7)
- Inspection permissions (3)
- Material permissions (6)
- Inventory permissions (3)
- Budget permissions (2)
- Team permissions (2)
- Document permissions (3)
- Comment permissions (2)

### Users
- 1 Super Admin (always)
- 5 Test Users (local only)

### Sample Data (ProjectSeeder)
- 1 Sample project
- 6 Project phases
- Multiple tasks per phase
- Team assignments

---

## 🎉 **Ready to Use!**

Run the seeders:
```bash
php artisan migrate:fresh --seed
```

Login as super admin:
```
http://localhost:8000/login
Email: admin@projectarchitect.com
Password: password
```

Start managing your system! 🚀
