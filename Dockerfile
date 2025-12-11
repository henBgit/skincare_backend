# שימוש ב־PHP 8.2 עם Apache
FROM php:8.2-apache

# הפעלת mod_rewrite (לא חובה אבל סטנדרטי)
RUN a2enmod rewrite

# העתקת הקבצים שלך לשרת
COPY . /var/www/html/

# הרשאות תקינות ל-Apache
RUN chown -R www-data:www-data /var/www/html

# חשיפת פורט 80 (Render עובד עליו)
EXPOSE 80
