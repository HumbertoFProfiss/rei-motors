<?php
/**
 * Include: Footer
 * Rei Motors - Reutilizável em todas as páginas
 */
?>

<footer class="footer">
    <div class="container">
        <div class="footer__grid">
            <div class="footer__col">
                <h3><?php echo LOJA_NOME; ?></h3>
                <p><?php echo LOJA_ENDERECO; ?></p>
                <p style="margin-top: var(--spacing-lg); font-size: var(--font-size-sm);">
                    Horário: <?php echo LOJA_HORARIO; ?>
                </p>
            </div>

            <div class="footer__col">
                <h4>Contato</h4>
                <p>
                    <a href="tel:<?php echo LOJA_TELEFONE; ?>" title="Ligar">
                        📞 <?php echo formatarTelefone(LOJA_TELEFONE); ?>
                    </a>
                </p>
                <p>
                    <a href="https://wa.me/55<?php echo preg_replace('/\D/', '', LOJA_WHATSAPP); ?>" title="WhatsApp" target="_blank">
                        💬 <?php echo formatarTelefone(LOJA_WHATSAPP); ?>
                    </a>
                </p>
                <p>
                    <a href="mailto:<?php echo LOJA_EMAIL; ?>" title="E-mail">
                        ✉️ <?php echo LOJA_EMAIL; ?>
                    </a>
                </p>
            </div>

            <div class="footer__col">
                <h4>Páginas</h4>
                <a href="<?php echo BASE_URL; ?>">Home</a>
                <a href="<?php echo BASE_URL; ?>estoque.php">Estoque</a>
                <a href="<?php echo BASE_URL; ?>comparar.php">Comparar Carros</a>
                <a href="<?php echo ADMIN_URL; ?>">Admin</a>
            </div>

            <div class="footer__col">
                <h4>Redes Sociais</h4>
                <div class="footer__sociais">
                    <a href="<?php echo $redes_sociais['facebook']; ?>" target="_blank" rel="noopener">Facebook</a>
                    <a href="<?php echo $redes_sociais['instagram']; ?>" target="_blank" rel="noopener">Instagram</a>
                    <a href="<?php echo $redes_sociais['youtube']; ?>" target="_blank" rel="noopener">YouTube</a>
                </div>
            </div>
        </div>

        <div class="footer__bottom">
            <p>&copy; <?php echo date('Y'); ?> <?php echo LOJA_NOME; ?> - Todos os direitos reservados</p>
            <p style="font-size:0.72rem;color:#555;margin-top:6px">
                Desenvolvido por <a href="https://rstechbr.com" target="_blank" rel="noopener"
                style="color:#D4AF37;text-decoration:none;font-weight:600">RS TECH</a>
            </p>
            <a href="<?php echo ADMIN_URL; ?>" class="footer__admin-link" title="Área Administrativa">🔒 Admin</a>
        </div>
    </div>
</footer>

<!-- ===== WHATSAPP FLUTUANTE ===== -->
<a href="https://wa.me/55<?php echo preg_replace('/\D/', '', LOJA_WHATSAPP); ?>" 
   class="whatsapp-flutuante" 
   title="Fale conosco via WhatsApp"
   target="_blank">
    💬
</a>
