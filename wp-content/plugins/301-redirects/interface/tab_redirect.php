<?php

/**
 * 301 Redirects Pro
 * https://wp301redirects.com/
 * (c) WebFactory Ltd, 2019 - 2021, www.webfactoryltd.com
 */

class WF301_tab_redirect extends WF301
{
    static function display()
    {
        global $wf_301_licensing;

        echo '<div class="tab-content">';

        echo '<table cellpadding="0" cellspacing="0" border="0" class="display" id="wf301-redirects-table">
            <thead>
                <tr>
                    <th style="width:50px;">Status</th>
                    <th style="width:160px;">Created</th>
                    <th style="width:60px;">Type</th>
                    <th style="text-align:left;padding-left: 8px;">Redirect From</th>
                    <th style="text-align:left;padding-left: 8px;">Redirect To</th>
                    <th style="width:60px;">Priority</th>
                    <th style="width:60px;">Hits</th>
                    <th style="width:100px;">Tags</th>
                    <th style="width:160px;">Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
            <tfoot>
                <tr>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Type</th>
                    <th style="text-align:left;padding-left: 8px;">Redirect From</th>
                    <th style="text-align:left;padding-left: 8px;">Redirect To</th>
                    <th>Priority</th>
                    <th>Hits</th>
                    <th>Tags</th>
                    <th>Actions</th>
                </tr>
            </tfoot>
        </table>';


        echo '</div>';

        //self::dialogs();
    } // display

    static function dialogs()
    {
        echo '<div id="redirect-rule-dialog" style="display: none;" title="UI Dialog"><span class="ui-helper-hidden-accessible"><input type="text"></span>';
        echo '<div id="redirect-rule-dialog-left">';
        echo '<div class="dialog-title">Add New Redirect Rule</div>';
        echo '<div class="wf301-dialog-close"><i class="wf301-icon wf301-close"></i></div>';
        echo '<table id="add_redirect_rule_form">';
        echo '<tr><td><label for="redirect_enabled">Redirect Enabled:</label></td><td><div class="toggle-wrapper">';
        echo '<input type="checkbox" id="redirect_enabled" checked type="checkbox" value="1" id="redirect_enabled" name="redirect_enabled">';
        echo '<label for="redirect_enabled" class="toggle"><span class="toggle_handler"></span></label>';
        echo '</div></td></tr>';
        echo '<tr><td><label for="redirect_url_from">Redirect From:</label></td><td><input type="text" id="redirect_url_from" name="redirect_url_from" value="' . home_url('/') . '" style="width:100%;" placeholder="The relative URL you want to redirect from" /></td></tr>';
        echo '<tr><td><label for="redirect_query">Query Parameters:</label></td><td><select id="redirect_query" name="redirect_query"><option value="ignore">Ignore all parameters</option><option value="exact">Exact match all parameters in any order</option><option value="pass">Ignore &amp; pass parameters to the target</option><option value="utm">Ignore all parameters except UTM variables</option></select></td></tr>';
        echo '<tr><td><label for="redirect_url_to">Redirect To:</label></td><td><input type="text" id="redirect_url_to" name="redirect_url_to" value="" style="width: 100%;" placeholder="https://" /></td></tr>';

        echo '<tr><td><label for="redirect_regex">Regex:</label></td><td><div class="toggle-wrapper">';
        echo '<input type="checkbox" id="redirect_regex" checked type="checkbox" value="1" name="redirect_regex">';
        echo '<label for="redirect_regex" class="toggle"><span class="toggle_handler"></span></label>';
        echo '</div></td></tr>';

        echo '<tr><td><label for="redirect_case_insensitive">Case Insensitive:</label></td><td><div class="toggle-wrapper">';
        echo '<input type="checkbox" id="redirect_case_insensitive" checked type="checkbox" value="1" name="redirect_case_insensitive">';
        echo '<label for="redirect_case_insensitive" class="toggle"><span class="toggle_handler"></span></label>';
        echo '</div></td></tr>';
        echo '<tr><td><label for="redirect_type">Redirect Type:</label></td><td><select id="redirect_type" name="redirect_type">
                <option value="301">301 - Permanent Redirect</option>
                <option value="302">302 - Found/Temporary Redirect</option>
                <option value="303">303 - See Other</option>
                <option value="304">304 - Not Modified</option>
                <option value="307">307 - Temporary Redirect</option>
                <option value="308">308 - Permanent Redirect</option>
                <option value="cloaking">Cloaking - Cloak Target URL behind Source URL</option>
            </select></td></tr>';
        echo '<tr><td><label for="redirect_position">Priority:</label></td><td><input type="number" id="redirect_position" name="redirect_position" value="0" style="width:100px;" /></td></tr>';
        echo '<tr><td><label for="redirect_tags">Tags:</label></td><td><input type="text" id="redirect_tags" name="redirect_tags" style="width:100%;"></td></tr>';
        echo '<tr><td></td><td>
            <input type="hidden" name="redirect_id" id="redirect_id" value="" />
            <div class="button button-primary" id="submit_redirect_rule" name="add_redirect_rule">Add Rule <i class="wf301-icon wf301-redirect"></i></div></td></tr>';
        echo '</table>';
        echo '</div>';

        echo '<div id="redirect-rule-dialog-right">';
        echo '<div class="dialog-title">Notes</div>';
        echo '<ul id="redirect-notes">
                <li>All URLs start with <code>/</code>. Have that in mind when writing source URLs. Example - http://example.com/sample-page/</li>
                <li>You can use <code>*</code> as a wildcard character in source URLs; it replaces any string of zero or more characters.</li>
                <li>You can also make use of regex in source URLs.</li>
                <li>Making use of only <code>*</code> wildcard or <code>.*</code> regex expression will create and infinite redirect loop. Do not make use of them alone.</li>
                <li>If you enable/disable permalinks please check all rules.</li>
                <li>Please remember to start external URLs with <code>http://</code>, if you don\'t they\'ll be considered internal.</li>
                <li>When writing internal URLs we suggest writing an absolute path, the one that starts with <code>/</code>. Example - http://example.com/internal-url/</li>
                <li>301 is the most commonly used redirect rule. It tells the browser (and Google) the original content has been permanently moved.</li>
                <li>Multiple source URLs can redirect to a single target URL, but source URLs have to be unique.</li>
            </ul>';
        echo '</div>';
        echo '</div>';

        echo '<div id="redirect-check-dialog" style="display: none;" title="UI Dialog"><span class="ui-helper-hidden-accessible"><input type="text"></span>';
        echo '<div class="redirect-check-result fas fa-times"></div>';
        echo '<div class="redirect-check-details"></div>';
        echo '<div class="redirect-check-attribution">Powered by <a target="_blank" href="https://redirect.li/">https://redirect.li/</a></div>';
        echo '</div>';
    }
} // class WF301_tab_redirect
