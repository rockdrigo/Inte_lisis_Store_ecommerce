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
					<img style="margin-top:5px" src="{{ Image|safe }}" />
				</td>
			</tr>
			<tr>
				<td style="padding-left:15px">
					<span class="Required">*</span> {% lang 'DestinationCountry' %}:
				</td>
				<td>
					<select name="destinationCountry" id="destinationCountry" class="Field250" onchange="ToggleCountry($(this).val());" />
						{{ Countries|safe }}
					</select>
				</td>
			</tr>
			<tr id="trstate" style="{{ HideStatesList|safe }}">
				<td style="padding-left:15px">
					<span class="Required">*</span> {% lang 'DestinationState' %}:
				</td>
				<td>
					<select name="destinationState{{ StateNameAppend|safe }}" id="destinationState" class="Field250">
						{{ StateList|safe }}
					</select>
				</td>
			</tr>
			<tr>
				<td style="padding-left:15px">
					<span class="Required">*</span> {% lang 'DestinationZip' %}:
				</td>
				<td>
					<input name="destinationZip" id="destinationZip" class="Field50" />
				</td>
			</tr>
			<tr>
				<td style="padding-left:15px">
					<span class="Required">*</span> {% lang 'PackageWeight' %}:
				</td>
				<td>
					<input name="weight" id="weight" class="Field50"> {{ WeightMeasurement|safe }}
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
</form>

<script type="text/javascript">
	function CheckQuoteForm() {
		if($('#trstate').css('display') != 'none' && !$('#destinationState').val()) {
			alert('{% lang 'SelectDestinationState' %}');
			$('#destinationState').focus();
			return false;
		}

		if(!$('#destinationZip').val()) {
			alert("{% lang 'EnterDestinationZip' %}");
			$('#destinationZip').focus();
			return false;
		}

		if(isNaN($('#weight').val()) || $('#weight').val() == "") {
			alert("{% lang 'EnterPackageWeight' %}");
			$('#weight').focus();
			$('#weight').select();
			return false;
		}

		return true;
	}

	function ToggleCountry(countryId) {
		$.ajax({
			url: 'remote.php',
			type: 'post',
			data: 'w=countryStates&c='+countryId,
			success: function(data)
			{
				$('#destinationState option').remove();
				var states = data.split('~');
				var numStates = 0;
				for(var i =0; i < states.length; ++i) {
					vals = states[i].split('|');
					if(!vals[0]) {
						continue;
					}
					$('#destinationState').append('<option value="'+vals[1]+'">'+vals[0]+'</option>');
					++numStates;
				}

				if(numStates == 0) {
					$('#trstate').hide();
					$('#destinationState').attr('name', 'destinationState2');
				}
				else {
					$('#trstate').show();
					$('#destinationState').attr('name', 'destinationState');
				}
			}
		});
	}
</script>