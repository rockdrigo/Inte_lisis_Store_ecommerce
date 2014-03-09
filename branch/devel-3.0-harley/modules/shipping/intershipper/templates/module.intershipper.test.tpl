<script type="text/javascript" src="script/common.js?{{ JSCacheToken }}"></script>

<script type="text/javascript">

	var url = 'remote.php';

</script>

<form action="index.php?ToDo=testShippingProviderQuote" method="post" onsubmit="return ValidateForm(CheckQuoteForm)">

<input type="hidden" name="methodId" value="{{ MethodId|safe }}" />

<fieldset style="margin:10px">

<legend>{% lang 'IntershipperShippingQuote' %}</legend>

<table width="100%" style="background-color:#fff" class="Panel">

	<tr>

		<td style="padding-left:15px">

			&nbsp;

		</td>

		<td>

			<img style="margin-top:5px" src="../modules/shipping/intershipper/images/{{ Image|safe }}" />

		</td>

	</tr>

	<tr>

		<td style="padding-left:15px">

			<span class="Required">*</span> {% lang 'IntershipperCarriers' %}:

		</td>

		<td>

			<select name="delivery_carriers[]" id="delivery_carriers" class="Field250 ISSelectReplacement" multiple="multiple" size="6">

				{{ Carriers|safe }}

			</select>

		</td>

	</tr>

	<tr>

		<td style="padding-left:15px">

			<span class="Required">*</span> {% lang 'IntershiperDestinationCountry' %}:

		</td>

		<td>

			<select name="delivery_country" id="delivery_country" class="Field250">

				{{ Countries|safe }}

			</select>

		</td>

	</tr>

	<tr>

		<td style="padding-left:15px">

			<span class="Required">*</span> {% lang 'IntershipperDestinationZip' %}:

		</td>

		<td>

			<input name="delivery_zip" id="delivery_zip" class="Field50">

		</td>

	</tr>

	<tr>

		<td style="padding-left:15px">

			<span class="Required">*</span> {% lang 'IntershipperWeight' %}:

		</td>

		<td>

			<input name="delivery_weight" id="delivery_weight" class="Field50">{{ Measurement|safe }}

		</td>

	</tr>

	<tr>

		<td width="120" style="padding-left:15px">

			<span class="Required">*</span> {% lang 'IntershipperWidth' %}:

		</td>

		<td>

			<input name="delivery_width" id="delivery_width" class="Field50" />{% lang 'Inches' %}

		</td>

	</tr>

	<tr>

		<td width="120" style="padding-left:15px">

			<span class="Required">*</span> {% lang 'IntershipperLength' %}:

		</td>

		<td>

			<input name="delivery_length" id="delivery_length" class="Field50" />{% lang 'Inches' %}

		</td>

	</tr>

	<tr>

		<td width="120" style="padding-left:15px">

			<span class="Required">*</span> {% lang 'IntershipperHeight' %}:

		</td>

		<td>

			<input name="delivery_height" id="delivery_height" class="Field50" />{% lang 'Inches' %}

		</td>

	</tr>

	<tr>

		<td style="padding-left:15px">

			&nbsp;

		</td>

		<td class="PanelBottom">

			<input type="submit" class="FormButton" style="width:120px" value="{% lang 'GetShippingQuote' %}">

		</td>

	</tr>

</table>

</fieldset>



<script type="text/javascript">



	function CheckQuoteForm() {

		var delivery_carriers = document.getElementById("delivery_carriers");

		var delivery_country = document.getElementById("delivery_country");

		var delivery_zip = document.getElementById("delivery_zip");

		var delivery_weight = document.getElementById("delivery_weight");

		var delivery_width = document.getElementById("delivery_width");

		var delivery_length = document.getElementById("delivery_length");

		var delivery_height = document.getElementById("delivery_height");



		if(delivery_carriers.selectedIndex == -1) {

			alert("{% lang 'IntershipperChooseCarrier' %}");

			delivery_carriers.focus();

			return false;

		}



		if(delivery_country.selectedIndex == 0) {

			alert("{% lang 'IntershipperChooseCountry' %}");

			delivery_country.focus();

			return false;

		}



		if(delivery_zip.value == "") {

			alert("{% lang 'IntershipperEnterDestinationZip' %}");

			delivery_zip.focus();

			return false;

		}



		if(isNaN(delivery_weight.value) || delivery_weight.value == "") {

			alert("{% lang 'IntershipperEnterValidWeight' %}");

			delivery_weight.focus();

			delivery_weight.select();

			return false;

		}



		if(isNaN(delivery_width.value) || delivery_width.value == "") {

			alert("{% lang 'IntershipperEnterValidWidth' %}");

			delivery_width.focus();

			delivery_width.select();

			return false;

		}



		if(isNaN(delivery_length.value) || delivery_length.value == "") {

			alert("{% lang 'IntershipperEnterValidLength' %}");

			delivery_length.focus();

			delivery_length.select();

			return false;

		}



		if(isNaN(delivery_height.value) || delivery_height.value == "") {

			alert("{% lang 'IntershipperEnterValidHeight' %}");

			delivery_height.focus();

			delivery_height.select();

			return false;

		}



		return true;

	}



</script>



