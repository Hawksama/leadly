<div class="wrap">
    <h2><?php _e( 'Scheduled SMS', 'wp-sms-pro' ); ?></h2>

    <form id="outbox-filter" method="get">
        <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>"/>
		<?php $list_table->search_box( __( 'Search', 'wp-sms-pro' ), 'search_id' ); ?>
		<?php $list_table->display(); ?>
    </form>
</div>