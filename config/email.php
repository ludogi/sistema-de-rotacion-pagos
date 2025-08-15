<?php

/**
 * Configuración del Servicio de Email
 * 
 * Este archivo contiene la configuración para el envío de emails
 * del sistema de restablecimiento de contraseña.
 */

return [
    // Configuración del remitente
    'from_email' => $_ENV['MAIL_FROM_EMAIL'] ?? 'noreply@innovantcafe.com',
    'from_name' => $_ENV['MAIL_FROM_NAME'] ?? 'Sistema Innovant Café',
    
    // Configuración SMTP (opcional)
    'smtp' => [
        'host' => $_ENV['MAIL_SMTP_HOST'] ?? 'localhost',        // Servidor SMTP
        'port' => $_ENV['MAIL_SMTP_PORT'] ?? 25,                 // Puerto SMTP
        'username' => $_ENV['MAIL_SMTP_USER'] ?? '',             // Usuario SMTP (si es necesario)
        'password' => $_ENV['MAIL_SMTP_PASS'] ?? '',             // Contraseña SMTP (si es necesario)
        'encryption' => $_ENV['MAIL_SMTP_ENCRYPTION'] ?? 'none'  // none, ssl, tls
    ],
    
    // URL base del sistema
    'base_url' => $_ENV['APP_URL'] ?? 'http://localhost:8088',
    
    // Configuración de tokens
    'token_expiration_hours' => 1,    // Horas hasta que expire el token
    
    // Configuración de emails
    'email_subject_prefix' => '[Innovant Café] ',
    
    // Configuración de logging
    'log_emails' => true,             // Registrar envíos de email
    'log_file' => 'logs/email.log'    // Archivo de log
];

/**
 * INSTRUCCIONES DE CONFIGURACIÓN:
 * 
 * 1. CAMBIAR URL BASE:
 *    - Cambia 'base_url' por la URL real de tu sistema
 *    - Ejemplo: 'https://tudominio.com' o 'http://192.168.1.100:8088'
 * 
 * 2. CONFIGURAR EMAIL REMITENTE:
 *    - Cambia 'from_email' por un email válido de tu dominio
 *    - Cambia 'from_name' por el nombre que quieres que aparezca
 * 
 * 3. CONFIGURAR SMTP (si tienes servidor SMTP):
 *    - Cambia 'host' por tu servidor SMTP
 *    - Cambia 'port' por el puerto correcto
 *    - Agrega 'username' y 'password' si es necesario
 * 
 * 4. PARA GMAIL:
 *    'smtp' => [
 *        'host' => 'smtp.gmail.com',
 *        'port' => 587,
 *        'username' => 'tuemail@gmail.com',
 *        'password' => 'tu_contraseña_de_aplicacion',
 *        'encryption' => 'tls'
 *    ]
 * 
 * 5. PARA OUTLOOK/HOTMAIL:
 *    'smtp' => [
 *        'host' => 'smtp-mail.outlook.com',
 *        'port' => 587,
 *        'username' => 'tuemail@outlook.com',
 *        'password' => 'tu_contraseña',
 *        'encryption' => 'tls'
 *    ]
 * 
 * 6. PARA SERVIDOR LOCAL:
 *    - Mantén 'localhost' y puerto 25 si tienes un servidor de correo local
 *    - O instala un servidor como Postfix, Sendmail, etc.
 */
