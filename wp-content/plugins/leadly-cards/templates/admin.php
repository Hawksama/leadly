<?php
/**
 * @package LeadlyPlugin
 */
?>

<div class="wrap">
    <h1><?= __('Leadly Plugin admin area', 'leadly') ?></h1>
    <form action='options.php' method='post'>

        <?php
        settings_fields( $this->get_setting('slug') );
        do_settings_sections( $this->get_setting('slug') );
        submit_button();
        ?>

    </form>
</div>