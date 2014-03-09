<form action="index.php?ToDo=testShippingProviderQuote" method="post" onsubmit="return ValidateForm(CheckQuoteForm)">

<input type="hidden" name="methodId" value="{{ MethodId|safe }}" />

<fieldset style="margin:10px">

<legend>{% lang 'CanadaPostShippingQuote' %}</legend>

<table width="100%" style="background-color:#fff" class="Panel">

	<tr>

		<td style="padding-left:15px">

			&nbsp;

		</td>

		<td>

			<img style="margin-top:5px" src="../modules/shipping/canadapost/images/{{ Image|safe }}" />

		</td>

	</tr>

	<tr>

		<td style="padding-left:15px">

			<span class="Required">*</span> {% lang 'CanadaPostDestinationCountry' %}:

		</td>

		<td>

			<select name="delivery_country" id="delivery_country" class="Field250">

				{{ Countries|safe }}

			</select>

		</td>

	</tr>

	<tr>

		<td width="120" style="padding-left:15px">

			<span class="Required">*</span> <label for="storename">{% lang 'CanadaPostDestinationState' %}:</label>

		</td>

		<td>

			<input type="text" name="delivery_state" id="delivery_state" class="Field250" />

		</td>

	</tr>

	<tr>

		<td style="padding-left:15px">

			<span class="Required">*</span> {% lang 'CanadaPostDestinationZip' %}:

		</td>

		<td>

			<input name="delivery_zip" id="delivery_zip" class="Field50">

		</td>

	</tr>

	<tr>

		<td style="padding-left:15px">

			<span class="Required">*</span> {% lang 'CanadaPostPackageWeight' %}:

		</td>

		<td>

			<input name="delivery_weight" id="delivery_weight" class="Field50">{{ WeightUnit|safe }}

		</td>

	</tr>

	<tr>

		<td width="120" style="padding-left:15px">

			<span class="Required">*</span> {% lang 'CanadaPostWidth' %}:

		</td>

		<td>

			<input type='text' class='Field50' name='delivery_width' id='delivery_width' />{{ LengthUnit|safe }}

		</td>

	</tr>

	<tr>

		<td width="120" style="padding-left:15px">

			<span class="Required">*</span> {% lang 'CanadaPostLength' %}

		</td>

		<td>

			<input type='text' class='Field50' name='delivery_length' id='delivery_length' />{{ LengthUnit|safe }}

		</td>

	</tr>

	<tr>

		<td width="120" style="padding-left:15px">

			<span class="Required">*</span> {% lang 'CanadaPostHeight' %}

		</td>

		<td>

			<input type='text' class='Field50' name='delivery_height' id='delivery_height' />{{ LengthUnit|safe }}

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

		var delivery_country = document.getElementById("delivery_country");

		var delivery_state = document.getElementById("delivery_state");

		var delivery_zip = document.getElementById("delivery_zip");

		var delivery_weight = document.getElementById("delivery_weight");

		var delivery_width = document.getElementById("delivery_width");

		var delivery_length = document.getElementById("delivery_length");

		var delivery_height = document.getElementById("delivery_height");



		if(delivery_country.selectedIndex == 0) {

			alert("{% lang 'CanadaPostChooseCountry' %}");

			delivery_country.focus();

			return false;

		}



		if(delivery_state.value == "") {

			alert("{% lang 'CanadaPostEnterState' %}");

			delivery_state.focus();

			return false;

		}



		if(delivery_zip.value == "") {

			alert("{% lang 'CanadaPostEnterZip' %}");

			delivery_zip.focus();

			return false;

		}



		if(isNaN(delivery_weight.value) || delivery_weight.value == "") {

			alert("{% lang 'CanadaEnterValidWeight' %}");

			delivery_weight.focus();

			delivery_weight.select();

			return false;

		}



		if(isNaN(delivery_width.value) || delivery_width.value == "") {

			alert("{% lang 'CanadaEnterValidWidth' %}");

			delivery_width.focus();

			delivery_width.select();

			return false;

		}



		if(isNaN(delivery_length.value) || delivery_length.value == "") {

			alert("{% lang 'CanadaEnterValidLength' %}");

			delivery_length.focus();

			delivery_length.select();

			return false;

		}



		if(isNaN(delivery_height.value) || delivery_height.value == "") {

			alert("{% lang 'CanadaEnterValidHeight' %}");

			delivery_height.focus();

			delivery_height.select();

			return false;

		}



		return true;

	}



</script>



