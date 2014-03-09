<form action="index.php?ToDo=testShippingProviderQuote" method="post" onsubmit="return ValidateForm(CheckQuoteForm)">

<input type="hidden" name="methodId" value="{{ MethodId|safe }}" />

<fieldset style="margin:10px">

<legend>{% lang 'UPSShippingQuote' %}</legend>

<table width="100%" style="background-color:#fff" class="Panel">

	<tr>

		<td style="padding-left:15px">

			&nbsp;

		</td>

		<td>

			<img style="margin-top:5px" src="../modules/shipping/ups/images/{{ Image|safe }}" />

		</td>

	</tr>

	<tr>

		<td style="padding-left:15px">

			<span class="Required">*</span> {% lang 'UPSDeliveryType' %}:

		</td>

		<td>

			<select name="delivery_type" id="delivery_type" class="Field250">

				{{ DeliveryTypes|safe }}

			</select>

		</td>

	</tr>

	<tr>

		<td style="padding-left:15px">

			<span class="Required">*</span> {% lang 'UPSDestinationCountry' %}:

		</td>

		<td>

			<select name="delivery_country" id="delivery_country" class="Field250">

				{{ Countries|safe }}

			</select>

		</td>

	</tr>

	<tr>

		<td style="padding-left:15px">

			<span class="Required">*</span> {% lang 'UPSDestinationType' %}:

		</td>

		<td>

			<select name="delivery_destination" id="delivery_destination" class="Field250">

				<option value="RES">{% lang 'UPSResidential' %}</option>

				<option value="COM">{% lang 'UPSCommercial' %}</option>

			</select>

		</td>

	</tr>

	<tr>

		<td style="padding-left:15px">

			<span class="Required">*</span> {% lang 'UPSDestinationZip' %}:

		</td>

		<td>

			<input name="delivery_zip" id="delivery_zip" class="Field50">

		</td>

	</tr>

	<tr>

		<td style="padding-left:15px">

			<span class="Required">*</span> {% lang 'UPSPackageWeight' %}:

		</td>

		<td>

			<input name="delivery_weight" id="delivery_weight" class="Field50">{{ WeightUnit|safe }}

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

</legend>



<script type="text/javascript">



	function CheckQuoteForm() {

		var delivery_zip = document.getElementById("delivery_zip");

		var delivery_weight = document.getElementById("delivery_weight");



		if(delivery_zip.value == "") {

			alert("{% lang 'UPSEnterDestinationZip' %}");

			delivery_zip.focus();

			return false;

		}



		if(isNaN(delivery_weight.value) || delivery_weight.value == "") {

			alert("{% lang 'UPSEnterValidWeight' %}");

			delivery_weight.focus();

			delivery_weight.select();

			return false;

		}



		return true;

	}



</script>



