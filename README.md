# Civora

Civil construction project CRM. Laravel app for projects, locations, pillar / wall / bridge work, materials, equipment, workers, daily site reports, and progress.

## Run

MySQL database: `civil_construction_crm`

```bash
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Open http://127.0.0.1:8000

Demo password for every account: `password`

| Role | Email ||
| --- | --- |
| Super Admin | admin@civora.test |
| Engineer | engineer@civora.test |
| Site Engineer | site@civora.test |
| Worker | worker@civora.test |
