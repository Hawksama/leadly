<?php
/**
 * 301 Redirects Pro
 * https://wp301redirects.com/
 * (c) WebFactory Ltd, 2019 - 2021, www.webfactoryltd.com
 */

class WF301_tab_redirect_log extends WF301
{
    static function display()
    {
        echo '<div class="wf301-stats-main wf301-chart-redirect" style="display:none"><canvas id="wf301-redirect-chart" style="height: 160px; width: 100%;"></canvas></div>';
        echo '<div class="wf301-stats-main wf301-stats-redirect" style="display:none">';
            WF301_stats::print_stats('redirect');
        echo '</div>';
        
        echo '<div class="tab-content">';
        echo '<div id="wf301-redirect-log-table-wrapper"><table cellpadding="0" cellspacing="0" border="0" class="display" id="wf301-redirect-log-table">
            <thead>
                <tr>
                    <th style="width:100px;">Date &amp; time</th>
                    <th style="width:380px;">URL</th>
                    <th>Referrer</th>
                    <th>Location/IP</th>
                    <th style="width:280px;">User Agent</th>
                    <th style="width:50px;"></th>
                </tr>
            </thead>
            <tbody></tbody>
            <tfoot>
                <tr>
                    <th>Date &amp; time</th>
                    <th>URL</th>
                    <th>Referrer</th>
                    <th>Location/IP</th>
                    <th>User Agent</th>
                    <th></th>
                </tr>
            </tfoot>
        </table></div>';

        echo '<div id="wf301-redirect-log-table-group-ip-wrapper"><table cellpadding="0" cellspacing="0" border="0" class="display" id="wf301-redirect-log-table-group-ip">
            <thead>
                <tr>
                    <th style="width:130px;">Date &amp; time</th>
                    <th>IP</th>
                    <th>Count</th>
                    <th>Top 3 URLs</th>
                    <th>Top 3 User Agents</th>
                    <th></th>
                </tr>
            </thead>
            <tbody></tbody>
            <tfoot>
                <tr>                    
                    <th>Date &amp; time</th>
                    <th>IP</th>
                    <th>Count</th>
                    <th>Top 3 URLs</th>
                    <th>Top 3 User Agents</th>
                    <th></th>
                </tr>
            </tfoot>
        </table></div>';

        echo '<div id="wf301-redirect-log-table-group-url-wrapper"><table cellpadding="0" cellspacing="0" border="0" class="display" id="wf301-redirect-log-table-group-url">
            <thead>
                <tr>
                    <th style="width:130px;">Date &amp; time</th>
                    <th>URL</th>
                    <th>Count</th>
                    <th>Top 3 IPs</th>
                    <th>Top 3 User Agents</th>
                    <th></th>
                </tr>
            </thead>
            <tbody></tbody>
            <tfoot>
                <tr>
                    <th>Date &amp; time</th>
                    <th>URL</th>
                    <th>Count</th>
                    <th>Top 3 IPs</th>
                    <th>Top 3 User Agents</th>
                    <th></th>
                </tr>
            </tfoot>
        </table></div>';

        echo '</div>';
    } // tab_redirect_logs
} // class WF301_tab_redirect_log
