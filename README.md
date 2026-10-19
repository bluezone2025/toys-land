# تويز لاند — متجر ألعاب الأطفال (Laravel 13 + Filament 5)

متجر إلكتروني عربي/إنجليزي مع لوحة تحكم كاملة وتحليلات للزوار والمبيعات.

## المميزات
- **المتجر:** الرئيسية (سلايدر، أعمار، أقسام، سلايدرات منتجات)، كل الألعاب مع بحث وفلترة وترتيب، صفحة القسم، صفحة المنتج (إصدارات وإضافات بأسعار)، السلة، الدفع، الفاتورة (PNG / PDF / طباعة)، تتبع الطلب، حساب العميل (الطلبات، المفضلة، العناوين، البيانات، كلمة المرور)، من نحن، الأسئلة الشائعة، تواصل معنا، الخصوصية، الشروط، الشحن.
- **اللغات والعملات:** عربي (RTL) وإنجليزي (LTR)، 6 دول خليجية بأسعار صرف ورسوم شحن قابلة للتعديل.
- **الدفع:** MyFatoorah (كي نت، فيزا/ماستر، Apple Pay) + الدفع عند الاستلام.
- **لوحة التحكم `/admin`:** المنتجات، الأقسام، الفئات العمرية، الإصدارات والإضافات، الطلبات (تغيير الحالة، الفاتورة، واتساب)، العملاء، أكواد الخصم، رسائل التواصل، سلايدر الرئيسية، صفحات الموقع، الأسئلة الشائعة، الدول والعملات، إعدادات المتجر الكاملة.
- **التقارير:** الزوار النشطين الآن (تحديث كل 10 ثواني)، زوار اليوم/الأسبوع/الشهر، مسار الشراء (زيارة ← سلة ← دفع ← شراء)، أكثر الصفحات، مصادر الزيارات، الأجهزة والمتصفحات، الزيارات حسب الساعة، كلمات البحث، المبيعات اليومية، حالات الطلبات، الأكثر مبيعاً.

## التشغيل محلياً
```bash
composer install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed      # يطبع بيانات دخول المدير
php artisan storage:link
npm install && npm run build
php artisan serve
```

## الرفع على cPanel (TMD Host)
1. **PHP:** من *MultiPHP Manager* اختر PHP 8.3 (أو أحدث). تأكد من تفعيل الإضافات: `intl`, `zip`, `fileinfo`, `gd`, `pdo_mysql`, `mbstring`.
2. **قاعدة البيانات:** من *MySQL Databases* أنشئ قاعدة ومستخدم وأعطه كل الصلاحيات.
3. **الملفات:** من *Git Version Control* اعمل Clone للريبو داخل مجلد خارج `public_html` (مثلاً `~/toysland`).
   ملفات الواجهة مبنية مسبقاً داخل `public/build` فلا تحتاج Node على السيرفر.
4. **مجلد الموقع:** من *Domains* غيّر Document Root للدومين إلى `toysland/public`.
   (إذا ما تقدر تغيره: الملف `.htaccess` الموجود بجذر المشروع يحوّل الطلبات إلى `public` تلقائياً.)
5. **من Terminal في cPanel:**
   ```bash
   cd ~/toysland
   composer install --no-dev --optimize-autoloader
   cp .env.example .env
   php artisan key:generate
   ```
   عدّل `.env`:
   ```
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://your-domain.com
   DB_CONNECTION=mysql
   DB_HOST=localhost
   DB_DATABASE=...
   DB_USERNAME=...
   DB_PASSWORD=...
   ADMIN_EMAIL=you@your-domain.com
   ADMIN_PASSWORD=كلمة-مرور-قوية
   MAIL_MAILER=smtp
   MAIL_HOST=mail.your-domain.com
   MAIL_PORT=465
   MAIL_USERNAME=no-reply@your-domain.com
   MAIL_PASSWORD=...
   MAIL_SCHEME=smtps
   MAIL_FROM_ADDRESS=no-reply@your-domain.com
   MYFATOORAH_API_KEY=...
   MYFATOORAH_BASE_URL=https://api.myfatoorah.com
   ```
   ثم:
   ```bash
   php artisan migrate --seed --force
   php artisan storage:link
   php artisan optimize
   php artisan filament:optimize
   ```
6. **Cron Job** (من *Cron Jobs*، كل دقيقة) لتنظيف بيانات الزيارات القديمة:
   ```
   * * * * * cd ~/toysland && php artisan schedule:run >> /dev/null 2>&1
   ```
7. **SSL:** فعّل AutoSSL / Let's Encrypt من cPanel.

### التحديثات لاحقاً
```bash
cd ~/toysland && git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize && php artisan filament:optimize
```

## MyFatoorah
- للتجربة: سجّل في بوابة MyFatoorah التجريبية وخذ مفتاح API التجريبي مع `MYFATOORAH_BASE_URL=https://apitest.myfatoorah.com`.
- للإطلاق: مفتاح الحساب الحقيقي مع `https://api.myfatoorah.com`.
- إذا ما في مفتاح، يشتغل الدفع عند الاستلام فقط (ويقدر المدير يطفي أي طريقة دفع من الإعدادات).

## الاختبارات
```bash
php artisan test
```
