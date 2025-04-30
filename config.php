<?php
/**
 * Configuration file for Arabic Content Publishing Platform
 * Contains database credentials and global settings
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'shajarat_qtttn');
define('DB_USER', 'shajarat_qtttn');  // Change to your database username
define('DB_PASS', '7,HN-sdy,_IU');  // Change to your database password
define('DB_CHARSET', 'utf8mb4');

// URL configuration
define('BASE_URL', 'https://shajarat.com/qtttn3/');  // Change to your domain

// Security settings
define('CSRF_SECRET', 'change_this_to_a_random_string');  // Change this to a random string
define('ADMIN_USERNAME', 'admin');  // Change admin username
define('ADMIN_PASSWORD', 'ChangeThisPassword');  // Change admin password
define('SESSION_NAME', 'admin_session');

// Content settings
define('MAX_TITLE_LENGTH', 200);
define('MAX_AUTHOR_LENGTH', 100);
define('MIN_CONTENT_LENGTH', 1);  // Changed from 10 to 1
define('MAX_CONTENT_LENGTH', 50000);

// Rate limiting settings
define('GENERAL_HOURLY_LIMIT', 5);
define('GENERAL_DAILY_LIMIT', 10);
define('SAUDI_HOURLY_LIMIT', 20);
define('SAUDI_DAILY_LIMIT', 40);

// Saudi IP ranges to include
$saudi_prefixes = ['5.42.', '46.52.', '62.149.', '78.93.', '188.54.', '212.138.'];

// Expiration options (in seconds)
$expiration_options = [
    '1_hour' => 3600,
    '1_day' => 86400,
    '1_week' => 604800,
    '1_month' => 2592000,
    '6_months' => 15552000,
    '1_year' => 31536000,
    'forever' => 0  // 0 means no expiration
];

// Error messages in Arabic
$error_messages = [
    'title_too_long' => 'العنوان طويل جدًا، يجب أن يكون أقل من 200 حرف',
    'author_too_long' => 'اسم الكاتب طويل جدًا، يجب أن يكون أقل من 100 حرف',
    'content_too_short' => 'المحتوى قصير جدًا، يجب أن يكون حرف واحد على الأقل.',
    'content_too_long' => 'المحتوى طويل جدًا، الحد الأقصى هو ٥٠٬٠٠٠ حرف. للتجاوز يُرجى التواصل معنا على واتساب وشرح الاستخدام: +966556361500',
    'content_required' => 'المحتوى مطلوب',
    'rate_limit_hour' => 'لقد تجاوزت الحد المسموح به من المنشورات في الساعة، يرجى المحاولة لاحقًا',
    'rate_limit_day' => 'لقد تجاوزت الحد المسموح به من المنشورات في اليوم، يرجى المحاولة لاحقًا',
    'invalid_expiration' => 'خيار انتهاء الصلاحية غير صالح',
    'post_not_found' => 'المنشور غير موجود أو تم حذفه',
    'edit_expired' => 'انتهت مدة التعديل، لا يمكن تعديل هذا المنشور بعد الآن',
    'invalid_token' => 'رمز الأمان غير صالح، يرجى تحديث الصفحة والمحاولة مرة أخرى',
    'database_error' => 'حدث خطأ في قاعدة البيانات، يرجى المحاولة لاحقًا',
    'admin_invalid' => 'اسم المستخدم أو كلمة المرور غير صحيحة',
    'ip_banned' => 'تم حظر عنوان IP الخاص بك، اتصل بالإدارة إذا كنت تعتقد أن هذا خطأ'
];

// UI text in Arabic
$ui_text = [
    'site_title' => 'منصة قطن | اكتب وانشر',
    'new_post' => 'منشور جديد',
    'title_placeholder' => 'العنوان (اختياري)',
    'author_placeholder' => 'الكاتب (اختياري)',
    'content_placeholder' => 'اكتب محتواك هنا...',
    'expiration' => 'مدة صلاحية المنشور:',
    'exp_1_hour' => 'ساعة واحدة',
    'exp_1_day' => 'يوم واحد',
    'exp_1_week' => 'أسبوع واحد',
    'exp_1_month' => 'شهر واحد',
    'exp_6_months' => '6 أشهر',
    'exp_1_year' => 'سنة واحدة',
    'exp_forever' => 'بلا انتهاء',
    'publish_button' => 'نشر',
    'success_message' => 'تم نشر المحتوى بنجاح! تم نسخ الرابط',
    'copy_link' => 'نسخ الرابط',
    'edit_button' => 'تعديل',
    'save_button' => 'حفظ التغييرات',
    'published_on' => 'نُشر في:',
    'expires_on' => 'ينتهي في:',
    'views' => 'المشاهدات:',
    'admin_login' => 'تسجيل دخول المدير',
    'username' => 'اسم المستخدم',
    'password' => 'كلمة المرور',
    'login_button' => 'تسجيل الدخول',
    'logout_button' => 'تسجيل الخروج',
    'admin_panel' => 'لوحة الإدارة',
    'recent_posts' => 'المنشورات الأخيرة',
    'delete_button' => 'حذف',
    'ban_ip' => 'حظر IP',
    'ip_address' => 'عنوان IP',
    'country' => 'الدولة',
    'date' => 'التاريخ',
    'actions' => 'الإجراءات',
    'confirm_delete' => 'هل أنت متأكد من أنك تريد حذف هذا المنشور؟',
    'confirm_ban' => 'هل أنت متأكد من أنك تريد حظر عنوان IP هذا؟',
    'banned_ips' => 'عناوين IP المحظورة',
    'unban' => 'إلغاء الحظر',
    'no_results' => 'لا توجد نتائج',
    'back_to_home' => 'العودة للصفحة الرئيسية'
];

?>