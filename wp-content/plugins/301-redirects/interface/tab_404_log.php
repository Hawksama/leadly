<?php
/**
 * 301 Redirects Pro
 * https://wp301redirects.com/
 * (c) WebFactory Ltd, 2019 - 2021, www.webfactoryltd.com
 */

class WF301_tab_404_log extends WF301
{
    static function display()
    {
        echo '<div class="wf301-stats-main wf301-chart-404" style="display:none"><canvas id="wf301-404-chart" style="height: 160px; width: 100%;"></canvas></div>';
        echo '<div class="wf301-stats-main wf301-stats-404" style="display:none">';
            WF301_stats::print_stats('404');
        echo '</div>';
        
        echo '<div class="tab-content">';
       
        
        echo '<div id="wf301-404-log-table-wrapper"><table cellpadding="0" cellspacing="0" border="0" class="display" id="wf301-404-log-table">
            <thead>
                <tr>
                    <th style="width:100px;">Date &amp; time</th>
                    <th style="width:380px;">URL</th>
                    <th>Referrer</th>
                    <th>Location/IP</th>
                    <th style="width:280px;">User Agent</th>
                    <th style="width:80px;"></th>
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
        </table>
        </div>';

        
        echo '<div id="wf301-404-log-table-group-ip-wrapper"><table cellpadding="0" cellspacing="0" border="0" class="display" id="wf301-404-log-table-group-ip">
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

        echo '<div id="wf301-404-log-table-group-url-wrapper"><table cellpadding="0" cellspacing="0" border="0" class="display" id="wf301-404-log-table-group-url">
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
    } // display
} // class WF301_tab_404_log
