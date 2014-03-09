<form action="index.php?ToDo=testShippingProviderQuote" method="post" onsubmit="return ValidateForm(CheckQuoteForm)">

<input type="hidden" name="methodId" value="{{ MethodId|safe }}" />

<fieldset style="margin:10px">

<legend>{% lang 'AusPostShippingQuote' %}</legend>

<table width="100%" style="background-color:#fff" class="Panel">

	<tr>

		<td style="padding-left:15px">

			&nbsp;

		</td>

		<td>

			<img style="margin-top:5px" src="../modules/shipping/auspost/images/{{ Image|safe }}" />

		</td>

	</tr>

	<tr>

		<td style="padding-left:15px">

			<span class="Required">*</span> {% lang 'AusPostDeliveryType' %}:

		</td>

		<td>

			<select name="delivery_type" id="delivery_type" class="Field250">

				{{ DeliveryTypes|safe }}

			</select>

		</td>

	</tr>

	<tr>

		<td style="padding-left:15px">

			<span class="Required">*</span> {% lang 'AusPostDestinationCountry' %}:

		</td>

		<td>

			<select name="delivery_country" id="delivery_country" class="Field250">

				{{ Countries|safe }}

			</select>

		</td>

	</tr>

	<tr>

		<td style="padding-left:15px">

			<span class="Required">*</span> {% lang 'AusPostDestinationPostcode' %}:

		</td>

		<td>

			<input name="delivery_postcode" id="delivery_postcode" class="Field50">

		</td>

	</tr>

	<tr>

		<td style="padding-left:15px">

			<span class="Required">*</span> {% lang 'AustPostWeight' %}:

		</td>

		<td>

			<input name="delivery_weight" id="delivery_weight" class="Field50">{{ WeightUnit|safe }}

		</td>

	</tr>

	<tr>

		<td style="padding-left:15px">

			<span class="Required">*</span> {% lang 'AustPostLength' %}:

		</td>

		<td>

			<input name="delivery_length" id="delivery_length" class="Field50">{{ LengthUnit|safe }}

		</td>

	</tr>

	<tr>

		<td style="padding-left:15px">

			<span class="Required">*</span> {% lang 'AustPostWidth' %}:

		</td>

		<td>

			<input name="delivery_width" id="delivery_width" class="Field50">{{ LengthUnit|safe }}

		</td>

	</tr>

	<tr>

		<td style="padding-left:15px">

			<span class="Required">*</span> {% lang 'AustPostHeight' %}:

		</td>

		<td>

			<input name="delivery_height" id="delivery_height" class="Field50">{{ LengthUnit|safe }}

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

		var delivery_postcode = document.getElementById("delivery_postcode");

		var delivery_weight = document.getElementById("delivery_weight");

		var delivery_length = document.getElementById("delivery_length");

		var delivery_width = document.getElementById("delivery_width");

		var delivery_height = document.getElementById("delivery_height");



		if(delivery_postcode.value == "") {

			alert("{% lang 'AusPostEnterDestinationPostcode' %}");

			delivery_postcode.focus();

			return false;

		}



		if(isNaN(delivery_weight.value) || delivery_weight.value == "") {

			alert("{% lang 'AusPostEnterValidWeight' %}");

			delivery_weight.focus();

			delivery_weight.select();

			return false;

		}



		if(isNaN(delivery_length.value) || delivery_length.value == "") {

			alert("{% lang 'AusPostEnterValidLength' %}");

			delivery_length.focus();

			delivery_length.select();

			return false;

		}



		if(isNaN(delivery_width.value) || delivery_width.value == "") {

			alert("{% lang 'AusPostEnterValidWidth' %}");

			delivery_width.focus();

			delivery_width.select();

			return false;

		}



		if(isNaN(delivery_height.value) || delivery_height.value == "") {

			alert("{% lang 'AusPostEnterValidHeight' %}");

			delivery_height.focus();

			delivery_height.select();

			return false;

		}



		return true;

	}



</script>



