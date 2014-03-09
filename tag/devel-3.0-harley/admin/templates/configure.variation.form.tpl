<script type="text/javascript">

function RecalculateProducts()
{
	var fp = document.forms.frmRecalculateVariation.elements;
	var selected = [];

	for(i = 0; i < fp.length; i++) {
		if(fp[i].type == "checkbox" && fp[i].checked)
			selected[selected.length] = fp[i].value;
	}

	if(selected.length == 0) {
		alert("{% jslang 'ChooseVariations' %}");
		return;
	}

	var resp = confirm('{% lang 'ProductVariationRecalculateConfirm' %}');
	
	if (resp == true)
	{
		$.iModal({
			type: 'ajax',
			method: 'post',
			url: 'remote.php?remoteSection=configure_variations&w=initRecaulculateCombinations&variationId={{ VariationId|safe }}&productIDx=' + selected.join(),
			//close: false,
			width: 600
		});
	}
}

function AsignVariationsForm()
{
	document.forms.frmAsignVariations.submit();
}

function SaveConfigVariationForm()
{
	document.forms.frmConfigVariation.submit();
}

function ConfirmCancel() {
	if(confirm("{% lang 'ConfirmCancelVariation' %}"))
		document.location.href = "index.php?ToDo=editProductVariation&variationId={{ VariationId|safe }}";
}

function selectAll (objectSent, turnways) {

	if (turnways == 1)
	{
		var i = 0;
		for(i;i<objectSent.length;i++)
		{
			objectSent[i].checked = false;
		}
	}
	else
	{
		var i = 0;
		for(i;i<objectSent.length;i++)
		{
			objectSent[i].checked = true;
		}
	}
}

function checkNumeric ()
{
	var i;
	var errPrice = new Array();
	var errWeight = new Array();
	var fieldsPrice = document.forms["frmConfigVariation"].elements["inp_vcprice[]"];
	var fieldsWeight = document.forms["frmConfigVariation"].elements["inp_vcweight[]"];
	//alert("--"+fieldsPrice.toString()+"--");
	for (i=0;i<fieldsPrice.length;i++)
	{
		//alert("--"+i+"--"+fieldsPrice[i].name+"--"+fieldsPrice[i].value+"--<br />");
		if (isNaN(fieldsPrice[i].value) || fieldsPrice[i].value == "")
		{
			errPrice.push(i);			
		}
		if (isNaN(fieldsWeight[i].value) || fieldsWeight[i].value == "")
		{
			errWeight.push(i);			
		}
	}

	if (errPrice.length>0)
	{
		var errmsg = 'Los siguientes campos deben ser numericos con dos digitos\nMontos de Precio:\n';
		for(i in errPrice)
		{
			errmsg += "Opcion ID: " + document.forms["frmConfigVariation"].elements["hdn_voptionid[]"][errPrice[i]].value + "\n";
			document.forms["frmConfigVariation"].elements["inp_vcprice[]"][errPrice[i]].style.backgroundColor = "red";
		}
		errmsg += "Montos de Peso:\n";
		for(i in errWeight)
		{
			errmsg += "Opcion ID: " + document.forms["frmConfigVariation"].elements["hdn_voptionid[]"][errPrice[i]].value + "\n";
			document.forms["frmConfigVariation"].elements["inp_vcweight[]"][errWeight[i]].style.backgroundColor = "red";
		}
		alert(errmsg);
		return false;
	}
	else return true;
}

function InitializeZeroes()
{
	var ans = confirm("Esta seguro de querer inicializar la forma? Todos los valores se regresaran a cero");

	if (ans == true)
	{
		var i;
		var fieldsPrice = document.forms["frmConfigVariation"].elements["inp_vcprice[]"];
		for (i=0;i<fieldsPrice.length;i++)
		{
			fieldsPrice[i].value = 0.0000;
		}
		var selectPrice = document.forms["frmConfigVariation"].elements["sel_vcpricediff[]"];
		for (i=0;i<selectPrice.length;i++)
		{
			selectPrice[i].selectedIndex = 0;
		}
		
		var fieldsWeight = document.forms["frmConfigVariation"].elements["inp_vcweight[]"];
		for (i=0;i<fieldsWeight.length;i++)
		{
			fieldsWeight[i].value = 0.0000;
		}
		var selectWeight = document.forms["frmConfigVariation"].elements["sel_vcweightdiff[]"];
		for (i=0;i<selectWeight.length;i++)
		{
			selectWeight[i].selectedIndex = 0;
		}
	}
}

</script>

<form enctype="multipart/form-data" action="index.php?ToDo={{ FormActionConfig|safe }}" id="frmConfigVariation" method="post">
<input type="hidden" name="variationId" id="productId" value="{{ VariationId|safe }}">
<div class="BodyContainer">
<table class="GridPanel SortableGrid AutoExpand" cellspacing="0" cellpadding="0" border="0" id="IndexGrid" style="width:100%;">
<tr>
	<td class="Heading1">{{ ConfigTitle|safe }}</td>
	<td align="right"><input type="reset" value="{% lang 'Cancel' %}" class="FormButton" onclick="ConfirmCancel()" /></td>
</tr>
<tr>
	<td class="Intro" colspan="2">
			{{ Message|safe }}
		<p>{% lang 'ConfigureVariationsIntro' %}</p>
	</td>
</tr>
<tr>
	<td>
		<table>
		<tr class="GridRow">
			<td class="Heading2">Nombre</td>
			<td class="Heading2">Valor</td>
			<td class="Heading2">Accion precio</td>
			<td class="Heading2">Monto accion precio</td>
			<td class="Heading2">Accion peso</td>
			<td class="Heading2">Monto accion peso</td>
		</tr>
		{{ Variations|safe }}
	</td>
</tr>
<tr>
	<td colspan="2">
		<input type="button" value="{% lang 'Save' %}" class="FormButton" onclick="SaveConfigVariationForm()" />
		<INPUT type='reset' value='Deshacer cambios' />
		<input type="button" value="{% lang 'InitializeForm' %}" class="FormButton" onclick="InitializeZeroes()" />
	</td>
</tr>
</table>
</form>
<br /> 
<form enctype="multipart/form-data" action="index.php?ToDo={{ FormActionAsignVariations|safe }}" id="frmAsignVariations" method="post">
<input type="hidden" name="variationId" id="productId" value="{{ VariationId|safe }}">
<table class="GridPanel SortableGrid AutoExpand" cellspacing="0" cellpadding="0" border="0" id="IndexGrid" style="width:100%;">
	<tr>
		<td class="Heading1">{{ AsignTitle|safe }}</td>
	</tr>
	<tr>
		<td class="Intro" colspan="2">
			<p>{% lang 'AsignVariationsIntro' %}</p>
		</td>
	</tr>
	<tr>
		<td>
			<table class="GridPanel SortableGrid AutoExpand" cellspacing="0" cellpadding="0" border="0" id="IndexGrid" style="width:100%;">
			<tr class="Heading3" >
				<tdcolspan="4">Productos por categoria</td>
			</tr>
			{{ ProductsTable|safe }}
			</table>
		</td>
	</tr>
	<tr>
		<td class="Heading1">Otras Opciones</td>
	</tr>
	<tr>
		<td><input type="checkbox" name="chk_prodoptionsrequired" />{% lang 'ProductOptionRequired' %}</td>
	</tr>
    <tr>
        <td>
            <input type="button" value="{{ AsignTitle|safe }}" class="FormButton" onclick="AsignVariationsForm()" />
			&nbsp;&nbsp;&nbsp;
            <a href="#" onclick="selectAll(document.forms.frmAsignVariations.elements['products[]'], 0);return false;">{% lang 'SelectAll' %}</a>
			&nbsp;&nbsp;&nbsp;
            <a href="#" onclick="selectAll(document.forms.frmAsignVariations.elements['products[]'], 1);return false;">{% lang 'UnselectAll' %}</a>
        </td>
    </tr>
</table>
</form>
<br />
<form enctype="multipart/form-data" action="index.php?ToDo={{ FormActionRecalculate|safe }}" id="frmRecalculateVariation" method="post">
<input type="hidden" name="variationId" id="productId" value="{{ VariationId|safe }}">
<table class="GridPanel SortableGrid AutoExpand" cellspacing="0" cellpadding="0" border="0" id="IndexGrid" style="width:100%;">
<tr>
	<td class="Heading1">{{ RecalculateTitle|safe }}</td>
</tr>
<tr>
	<td class="Intro" colspan="2">
		<p>{% lang 'RecalculateVariationsIntro' %}</p>
	</td>
</tr>
<tr>
	<td>
		<table class="GridPanel SortableGrid AutoExpand" cellspacing="0" cellpadding="0" border="0" id="IndexGrid" style="width:100%;">
			<tr>
				<td colspan="4" class="Heading2">Productos a recalcular</td>
			</tr>
			{{ ProductsToRecalculate|safe }}
		</table>
	</td>
</tr>
<tr>
	<td>
		<!--<input type="button" value="{{ RecalculateTitle|safe }}" class="FormButton" onclick="RecalculateProducts();" />-->
		<input type="submit" name="RecalculateFormRecalculate" value="{{ RecalculateTitle|safe }}" class="FormButton" onclick="return confirm('{% lang 'ProductVariationRecalculateConfirm' %}');" />
		<input type="submit" name="RecalculateFormUnasign" value="{% lang 'UnasignVariation' %}" class="FormButton" onclick="return confirm('{% lang 'ProductVariationUnasignConfirm' %}');" />
		&nbsp;&nbsp;&nbsp;
		<a href="#" onclick="selectAll(document.forms.frmRecalculateVariation.elements['chk_product[]'], 0);return false;">{% lang 'SelectAll' %}</a>
		&nbsp;&nbsp;&nbsp;
		<a href="#" onclick="selectAll(document.forms.frmRecalculateVariation.elements['chk_product[]'], 1);return false;">{% lang 'UnselectAll' %}</a>
	</td>
	<td align="right"><input type="reset" value="{% lang 'Cancel' %}" class="FormButton" onclick="ConfirmCancel()" /></td>	
</tr>
</table>
</div>
</form>
