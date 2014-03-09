<div class="toolbar">
	<h1 id="pageTitle">{% lang 'Order' %} #{{ OrderId|safe }}</h1>
        <a style="position:absolute; left:5px; top:8px; width:30px" class="button" href="javascript:history.go(-2)" type="submit">Back</a>
	<a style="width:59px" class="button" href="{{ ShopPath|safe }}/admin/index.php?ToDo=viewOrders" type="submit">{% lang 'AllOrders' %}</a>
</div>
<ul id="order" title="{% lang 'Order' %} #{{ OrderId|safe }}" selected="true">
	<li style="height:25px" class="subMenu">
		<ul class="tab">
			<li id="od" onclick="SubMenu(this)">{% lang 'OrderDetails' %}</li>
			<li id="om" onclick="SubMenu(this)" class="tabSelected">{% lang 'OrderMessages' %}</li>
		</ul>
	</li>
	{{ MessageGrid|safe }}
	<li class="group">{% lang 'PostNewMessage' %}</li>
	<li style="border-bottom:solid 1px transparent">
		<form enctype="multipart/form-data" action="index.php?ToDo=saveNewOrderMessage" onsubmit="return CheckMessageForm()" method="post">
			<input type="hidden" name="orderId" value="{{ OrderId|safe }}">
			<input type="hidden" name="messageId" value="{{ MessageId|safe }}">
			<input type="hidden" name="subject" value="{{ MessageSubject|safe }}">
			<textarea id="message" name="message" style="width:93%; height:50px; font-size:15px; color:#CACACA" onclick="SetupTextbox()">{% lang 'TapToTypeMessage' %}</textarea>
			<input type="submit" value="Send Message to Customer" style="width:98%" />
		</form>
	</li>
</ul>

<script type="text/javascript">

	function SubMenu(Tab) {
		switch(Tab.id) {
			case "od": {
				document.location.href = "index.php?ToDo=viewSingleOrder&o={{ OrderId|safe }}";
				break;
			}
			case "om": {
				document.location.reload();
				break;
			}
		}
	}

	function SetupTextbox() {
		var m = document.getElementById("message");
		m.style.color = "#000";
		m.value = "";
	}

	function CheckMessageForm() {
		var m = document.getElementById("message");

		if(m.value == "" || m.value == "{% lang 'TapToTypeMessage' %}") {
			alert("{% lang 'EnterMessageShort' %}");
			return false;
		}

		return true;
	}

</script>