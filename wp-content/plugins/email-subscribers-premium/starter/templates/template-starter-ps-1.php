<?php
$template_1 = array(
					'es_templ_heading' => 'New post alert : {{POSTTITLE}}',
					'es_templ_body' => '<table class="message-container">
		<tr><td class="mcnTextContent"><h2>Your Site Title</h2><div style="text-align: center;"><span style="color:#696969;font-size:14px"><strong>A small description </span></div></td></tr>
		<tr><td><span style="font-size:20px;"><strong>{{POSTTITLE}}</strong></span></td></tr>
		<tr><td><em><span  style="font-size: 0.8em;color: gray;">{{DATE}}</span></em></td></tr>
		<tr><td>{{POSTFULL}}</td></tr>
	</table>',
					'es_templ_status' => 'Published',
					'es_email_type' => 'post_notification',
					'es_custom_css' => '<style type="text/css">
										.message-container{
											margin:0 auto;
										}
										 .message-container td p{
											margin:10px 0;
											padding:0;
						                       font-size:15px;
						                       max-width:600px
										}
										.message-container table{
											border-collapse:collapse;
										}
										.message-container h1,.message-container h2,.message-container h3,.message-container h4,.message-container h5,.message-container h6{
											display:block;
											margin:0;
											padding:0;
										}
										.message-container img,.message-container a img{
											border:0;
											height:auto;
											outline:none;
											text-decoration:none;
										}
										body,#bodyTable,#bodyCell{
											height:100%;
											margin:0;
											padding:0;
											width:100%;
										}
										
										table{
											mso-table-lspace:0pt;
											mso-table-rspace:0pt;
										}
										
										a[href^=tel],a[href^=sms]{
											color:inherit;
											cursor:default;
											text-decoration:none;
										}
										.message-container p,.message-container a,.message-container li,.message-container td,body,table,blockquote{
											-ms-text-size-adjust:100%;
											-webkit-text-size-adjust:100%;
										}
										#bodyCell{
											border-top:4px solid #000000;
										}
										
										.message-container h1{
											color:#000000 !important;
											display:block;
											font-family:Helvetica;
											font-size:60px;
											font-style:normal;
											font-weight:bold;
											line-height:25%;
											letter-spacing:-1px;
											margin:0;
											text-align:center;
										}
										.message-container h2{
											color: #000000 !important;
										    display: block;
										    font-family: Helvetica;
										    font-size: 26px;
										    font-style: normal;
										    font-weight: bold;
										    // line-height: 80%;
										    letter-spacing: normal;
										    margin: 0;
										    text-align: center;
										    // border-bottom: 2px black solid;
										    padding-bottom: 0.5em;
										}
										.message-container h3{
											color:#000000 !important;
											display:block;
											font-family:Helvetica;
											font-size:20px;
											font-style:normal;
											font-weight:bold;
											line-height:25%;
											letter-spacing:normal;
											margin:0;
											text-align:center;
										}
										.message-container h4{
											color:#000000 !important;
											display:block;
											font-family:Helvetica;
											font-size:16px;
											font-style:normal;
											font-weight:bold;
											line-height:125%;
											letter-spacing:normal;
											margin:0;
											text-align:left;
										}
										.mcnTextContent{
											padding: 0px 18px 20px;
											    line-height: 125%;
											    mso-line-height-rule: exactly;
											    -ms-text-size-adjust: 100%;
											    -webkit-text-size-adjust: 100%;
											    word-break: break-word;
											    color: #000000;
											    font-family: Helvetica;
											    font-size: 15px;
											    text-align: left;
											}
								</style>',
					'es_thumbnail' => ES_PLUGIN_URL . 'starter/templates/images/template-st-ps-1.png'
				);
return $template_1;
