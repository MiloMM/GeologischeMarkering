<div class="container">
    <h1>LoginController/showProfile</h1>

    <div class="box">
        <h2>Your profile</h2>

        <!-- echo out the system feedback (error and success messages) -->
        <?php $this->renderFeedbackMessages(); ?>

        <div>Your username: <?php echo Session::get('user_name'); ?></div>
        <div>Your email: <?php echo Session::get('user_email'); ?></div>
    </div>
</div>