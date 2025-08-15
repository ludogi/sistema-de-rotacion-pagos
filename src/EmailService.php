<?php

namespace InnovantCafe;

class EmailService
{
    private $fromEmail;
    private $fromName;
    private $smtpHost;
    private $smtpPort;
    private $smtpUsername;
    private $smtpPassword;
    
    public function __construct()
    {
        // Cargar configuración
        $configPath = __DIR__ . '/../config/email.php';
        if (file_exists($configPath)) {
            $config = require $configPath;
            
            $this->fromEmail = $config['from_email'] ?? 'noreply@innovantcafe.com';
            $this->fromName = $config['from_name'] ?? 'Sistema Innovant Café';
            $this->smtpHost = $config['smtp']['host'] ?? 'localhost';
            $this->smtpPort = $config['smtp']['port'] ?? 25;
            $this->smtpUsername = $config['smtp']['username'] ?? '';
            $this->smtpPassword = $config['smtp']['password'] ?? '';
        } else {
            // Configuración por defecto si no existe el archivo
            $this->fromEmail = 'noreply@innovantcafe.com';
            $this->fromName = 'Sistema Innovant Café';
            $this->smtpHost = 'localhost';
            $this->smtpPort = 25;
            $this->smtpUsername = '';
            $this->smtpPassword = '';
        }
    }
    
    /**
     * Envía un email usando la función mail() de PHP
     */
    public function enviarEmail($to, $subject, $message, $isHTML = true)
    {
        try {
            // Headers del email
            $headers = [];
            $headers[] = 'From: ' . $this->fromName . ' <' . $this->fromEmail . '>';
            $headers[] = 'Reply-To: ' . $this->fromEmail;
            $headers[] = 'X-Mailer: PHP/' . phpversion();
            
            if ($isHTML) {
                $headers[] = 'MIME-Version: 1.0';
                $headers[] = 'Content-type: text/html; charset=UTF-8';
            }
            
            // Enviar email
            $result = mail($to, $subject, $message, implode("\r\n", $headers));
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Email enviado correctamente'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error al enviar email'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Envía email de restablecimiento de contraseña
     */
    public function enviarEmailResetPassword($email, $nombre, $token, $baseUrl)
    {
        $subject = 'Restablecimiento de Contraseña - Sistema Innovant Café';
        
        $resetLink = $baseUrl . '/reset_password.php?token=' . $token;
        
        $message = $this->getTemplateResetPassword($nombre, $resetLink);
        
        return $this->enviarEmail($email, $subject, $message, true);
    }
    
    /**
     * Template HTML para el email de restablecimiento
     */
    private function getTemplateResetPassword($nombre, $resetLink)
    {
        return '
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Restablecimiento de Contraseña</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 20px;
                }
                .header {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 30px;
                    text-align: center;
                    border-radius: 10px 10px 0 0;
                }
                .content {
                    background: #f8f9fa;
                    padding: 30px;
                    border-radius: 0 0 10px 10px;
                }
                .button {
                    display: inline-block;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 15px 30px;
                    text-decoration: none;
                    border-radius: 25px;
                    font-weight: bold;
                    margin: 20px 0;
                }
                .footer {
                    text-align: center;
                    margin-top: 30px;
                    padding-top: 20px;
                    border-top: 1px solid #ddd;
                    color: #666;
                    font-size: 12px;
                }
                .warning {
                    background: #fff3cd;
                    border: 1px solid #ffeaa7;
                    padding: 15px;
                    border-radius: 5px;
                    margin: 20px 0;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>🔑 Restablecimiento de Contraseña</h1>
                <p>Sistema Innovant Café</p>
            </div>
            
            <div class="content">
                <h2>Hola ' . htmlspecialchars($nombre) . ',</h2>
                
                <p>Has solicitado restablecer tu contraseña en el Sistema Innovant Café.</p>
                
                <p>Para continuar con el proceso, haz clic en el siguiente botón:</p>
                
                <div style="text-align: center;">
                    <a href="' . htmlspecialchars($resetLink) . '" class="button">
                        🔑 Restablecer Contraseña
                    </a>
                </div>
                
                <p>O copia y pega este enlace en tu navegador:</p>
                <p style="word-break: break-all; background: #e9ecef; padding: 10px; border-radius: 5px;">
                    ' . htmlspecialchars($resetLink) . '
                </p>
                
                <div class="warning">
                    <strong>⚠️ Importante:</strong>
                    <ul>
                        <li>Este enlace expira en <strong>1 hora</strong></li>
                        <li>Solo puedes usarlo <strong>una vez</strong></li>
                        <li>Si no solicitaste este cambio, ignora este email</li>
                    </ul>
                </div>
                
                <p>Si tienes alguna pregunta, contacta con el administrador del sistema.</p>
                
                <p>Saludos,<br>
                <strong>Equipo Innovant Café</strong></p>
            </div>
            
            <div class="footer">
                <p>Este es un email automático, por favor no respondas a este mensaje.</p>
                <p>&copy; ' . date('Y') . ' Sistema Innovant Café. Todos los derechos reservados.</p>
            </div>
        </body>
        </html>';
    }
    
    /**
     * Configura parámetros SMTP personalizados
     */
    public function configurarSMTP($host, $port, $username = '', $password = '')
    {
        $this->smtpHost = $host;
        $this->smtpPort = $port;
        $this->smtpUsername = $username;
        $this->smtpPassword = $password;
    }
    
    /**
     * Configura email remitente personalizado
     */
    public function configurarRemitente($email, $nombre)
    {
        $this->fromEmail = $email;
        $this->fromName = $nombre;
    }
}
