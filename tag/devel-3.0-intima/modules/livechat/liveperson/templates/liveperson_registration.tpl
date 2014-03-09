<label style="display: block;"><input type="radio" checked="checked" name="lp_register" onclick="ToggleLivePerson(this.value);" value="1" /> {% lang 'LivePersonDontHaveCreate' %}</label>
<div id="lp_register_show">
	<img src="images/nodejoin.gif" alt="" /> <input type="button" value="{% lang 'LivePersonCreateAccount' %}" onclick="CreateLivePersonAccount()" class="FormButton" style="width:170px" />
</div>
<label style="display: block;"><input type="radio" name="lp_register" onclick="ToggleLivePerson(this.value);" value="0" /> {% lang 'LivePersonHaveAccount' %}</label>
<div style="display: none;" id="lp_existing_show">
	<img src="images/nodejoin.gif" alt="" style="float: left;" />
	<div style="float: left; padding-top: 5px; padding-left: 10px;">
		{% lang 'LivePersonExistingUserInstructions' %}
	</div>
</div>
<script type="text/javascript">
	function ToggleLivePerson(value)
	{
		if(value == 1) {
			$('#lp_register_show').show();
			$('#lp_existing_show').hide();
			$('.properties_livechat_liveperson:gt(1)').hide();
		}
		else {
			$('#lp_register_show').hide();
			$('#lp_existing_show').show();
			$('.properties_livechat_liveperson:gt(2)').show();
		}
	}

	function CreateLivePersonAccount()
	{
		tb_show('', "index.php?ToDo=liveChatSettingsCallback&module=livechat_liveperson&func=ShowLivePersonRegistration&height=320&width=460&modal=true&TB_iframe=true");
	}

	function IntegrateLivePerson(siteId)
	{
		if($('#livechat_liveperson_position').val() == 'panel') {
			code = $('#lp_side_button').val();
		}
		else {
			code = $('#lp_top_button').val();
		}
		code = code.replace(/%%SIDEID%%/g, siteId);
		$('#livechat_liveperson_buttontag').val(code);
		code = $('#lp_monitor_tag').val();
		code = code.replace(/%%SIDEID%%/g, siteId);
		$('#livechat_liveperson_monitortag').val(code);
		$('#livechat_liveperson_siteid').val(siteId);
		$('#frmLiveChatSettings').submit();
	}

	function UpdatePosition(position)
	{
		$('#livechat_liveperson_position').val(position);
	}
</script>
<textarea name="lp_side_button" id="lp_side_button" style="display: none">
	<!-- BEGIN LivePerson Button Code --><div  ><table border='0' cellspacing='2' cellpadding='2'><tr><td align="center"></td><td align='center'><a id="_lpChatBtn" href='https://server.iad.liveperson.net/hc/%%SIDEID%%/?cmd=file&amp;file=visitorWantsToChat&amp;site=%%SIDEID%%&amp;byhref=1&imageUrl=https://server.iad.liveperson.net/hcp/Gallery/ChatButton-Gallery/English/General/2a' target='chat%%SIDEID%%'  onClick="lpButtonCTTUrl = 'https://server.iad.liveperson.net/hc/%%SIDEID%%/?cmd=file&file=visitorWantsToChat&site=%%SIDEID%%&imageUrl=https://server.iad.liveperson.net/hcp/Gallery/ChatButton-Gallery/English/General/2a&referrer='+escape(document.location); lpButtonCTTUrl = (typeof(lpAppendVisitorCookies) != 'undefined' ? lpAppendVisitorCookies(lpButtonCTTUrl) : lpButtonCTTUrl); window.open(lpButtonCTTUrl,'chat%%SIDEID%%','width=475,height=400,resizable=yes');return false;" ><img src='https://server.iad.liveperson.net/hc/%%SIDEID%%/?cmd=repstate&site=%%SIDEID%%&channel=web&&ver=1&imageUrl=https://server.iad.liveperson.net/hcp/Gallery/ChatButton-Gallery/English/General/2a' name='hcIcon' border=0></a></td></tr><tr><td>&nbsp;</td><td align='center'><div style="margin-top:5px;"><span style="font-size:10px; font-family:Arial, Helvetica, sans-serif;"><a href="http://solutions.liveperson.com/live-chat" style="text-decoration:none; color:#000" target="_blank"><b>Live Chat</b></a><span style="color:#000"> by </span><a href="http://www.liveperson.com/" style="text-decoration:none; color:#FF9900" target="_blank">LivePerson</a></span></div></td></tr><tr><td>&nbsp;</td><td align='center'><a href='http://solutions.liveperson.com/customer-service/?site=%%SIDEID%%&amp;domain=server.iad.liveperson.net&amp;origin=chatbutton' target='_blank'  onClick="javascript:window.open('http://solutions.liveperson.com/customer-service/?site=%%SIDEID%%&domain=server.iad.liveperson.net&origin=chatbutton&referrer='+escape(document.location));return false;" ><img src='https://server.iad.liveperson.net/hc/%%SIDEID%%/?cmd=rating&site=%%SIDEID%%&type=indicator' name='hcRating' alt='Customer Service Rating by LivePerson' border=0></a></td></tr>
	</table>
	</div><!-- END LivePerson Button code -->
</textarea>
<textarea name="lp_top_button" id="lp_top_button" style="display: none">
<!-- BEGIN LivePerson Button Code --><div  ><a id="_lpChatBtn" href='https://server.iad.liveperson.net/hc/12177928/?cmd=file&amp;file=visitorWantsToChat&amp;site=12177928&amp;byhref=1&imageUrl=https://server.iad.liveperson.net/hcp/Gallery/ChatButton-Gallery/English/General/1a' target='chat12177928'  onClick="javascript:window.open('https://server.iad.liveperson.net/hc/12177928/?cmd=file&file=visitorWantsToChat&site=12177928&imageUrl=https://server.iad.liveperson.net/hcp/Gallery/ChatButton-Gallery/English/General/1a&referrer='+escape(document.location),'chat12177928','width=475,height=400,resizable=yes');return false;" >Live Chat <img src="%%IMG_DIRECTORY%%/ChatIcon.gif" border="0" alt="" /></a></div><!-- END LivePerson Button code -->
</textarea>
<textarea name="lp_monitor_tag" id="lp_monitor_tag" style="display: none">
	<!-- BEGIN Invitation Positioning  -->
	<script type="text/javascript">
	var lpPosY = 100;
	</script>
	<!-- END Invitation Positioning  -->

	<!-- BEGIN HumanTag Monitor. DO NOT MOVE! MUST BE PLACED JUST BEFORE THE /BODY TAG --><script language='javascript' src='https://server.iad.liveperson.net/hc/%%SIDEID%%/x.js?cmd=file&file=chatScript3&site=%%SIDEID%%&&imageUrl=https://server.iad.liveperson.net/hcp/Gallery/ChatButton-Gallery/English/General/2a'> </script><!-- END HumanTag Monitor. DO NOT MOVE! MUST BE PLACED JUST BEFORE THE /BODY TAG -->
</textarea>