<?php
require_once '../includes/header.php';
require_once '../includes/navbar.php';

$file_path = 'C:\xampp\htdocs\Official\steg_immo\[Latex]\main\main.pdf'; 

$file_url = '/Official/steg_immo/[Latex]/main/main.pdf'; 
?>

<div class="pdf-container" style="width: 100%; max-width: 1000px; margin: 20px auto;">    
    <?php if (file_exists($file_path)): ?>
        <iframe 
            src="<?php echo $file_url; ?>" 
            width="100%" 
            height="800px" 
            style="border: none; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
            <p>Your browser does not support iframes. 
               <a href="<?php echo $file_url; ?>">Click here to download the PDF.</a>
            </p>
        </iframe>
    <?php else: ?>
        <div style="color: red; padding: 20px; text-align: center;">
            <strong>Error:</strong> The file "rapport.pdf" does not exist at the specified path.
        </div>
    <?php endif; ?>
</div>

<?php
require_once '../includes/footer.php';
?>