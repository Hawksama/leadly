<?php
/**
 * 301 Redirects Pro
 * https://wp301redirects.com/
 * (c) WebFactory Ltd, 2019 - 2021, www.webfactoryltd.com
 */

class WF301_tab_license extends WF301
{
    static function display()
    {
        global $wf_301_licensing;
        $options = $wf_301_licensing->get_license();

        echo '<div class="wf301-tab-title"><i class="wf301-icon wf301-check"></i> License</div>';
        echo '<p class="wf301-tab-description">Enter your license key here, to activate the plugin.<br />Your License key is visible on the screen, right after purchasing. You can also find it in the confirmation email sent to the email address provided on purchase.</p>';
        
        echo '<div class="tab-content">';
        echo '<table class="form-table"><tbody><tr>
        <th scope="row"><label for="license-key">License Key</label></th>
        <td>
        <input class="regular-text" type="text" id="license-key" value="" placeholder="' . (empty($options['license_key']) ? '12345678-12345678-12345678-12345678' : substr(esc_attr($options['license_key']), 0, 8) . '-************************') . '">
            </td></tr>';

        echo '<tr><th scope="row"><label for="">' . __('License Status', '301-redirects') . '</label></th><td>';
        if ($wf_301_licensing->is_active()) {
            $license_formatted = $wf_301_licensing->get_license_formatted();
            echo '<b style="color: #66b317;">Active</b><br>
            Type: ' . $license_formatted['name_long'];
            echo '<br>Valid ' . $license_formatted['valid_until'] . '</td>';
        } else { // not active
            echo '<strong style="color: #ea1919;">Inactive</strong>';
            if (!empty($wf_301_licensing->get_license('error'))) {
                echo '<br>Error: ' . $wf_301_licensing->get_license('error');
            }
        }
        echo '</td></tr>';
        echo '</tbody></table>';

        echo '<div class="license-buttons">';
        echo '<a href="#" id="save-license" data-text-wait="Validating. Please wait." class="button button-primary">Save &amp; Activate License <i class="wf301-icon wf301-check"></i></a>';
        
        if ($wf_301_licensing->is_active()) {
            echo '&nbsp; &nbsp;<a href="#" id="deactivate-license" class="button button-delete">Deactivate License</a>';
        } else {
            echo '&nbsp; &nbsp;<a href="#" class="button button-primary" data-text-wait="Validating. Please wait." id="wf301_keyless_activation">Keyless Activation</a>';
        }
        echo '<p class="wf301-tab-description-small">If you don\'t have a license - <a target="_blank" href="' . WF301_admin::generate_web_link('license-tab') . '">purchase one now</a>. In case of problems please <a href="#" class="open-beacon">contact support</a>. You can manage your licenses in the <a target="_blank" href="' . WF301_admin::generate_dashboard_link('license-tab') . '">301 Redirects Dashboard</a></p>';
        
        echo '</div>';


        if ($wf_301_licensing->is_active('white_label')) {
            echo '<h4>White-Label License Mode</h4>';
            echo '<p>Enabling the white-label license mode hides the License and Support tabs, and removes all visible mentions of WebFactory Ltd.<br>To disable it append <strong>&amp;wf301_wl=false</strong> to the WP 301 Redirects settings page URL.
                Or save this URL and open it when you want to disable the white-label license mode:<br> <a href="' . admin_url('options-general.php?page=301redirects&wf301_wl=false') . '">' . admin_url('options-general.php?page=301redirects&wf301_wl=false') . '</a></p>';
            echo '<p><a href="' . admin_url('options-general.php?page=301redirects&wf301_wl=true') . '" class="button button-secondary">Enable White-Label License Mode</a></p>';
        }

        echo '</div>';
    } // display
} // class WF301_tab_license
