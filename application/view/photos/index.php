<div class="container">
    <div class="login-default-box">
        <h1>Upload photo</h1>
        <form action="<?php echo URL; ?>photos/addPhoto" method="post" enctype="multipart/form-data">
            <label>Select file to upload:</label>
            <input type="file" name="fileToUpload" id="fileToUpload" required />
            <input type="submit" class="login-submit-button" value="Upload Image" name="submit" />
        </form>
	
         
    </div>
</div>
