<link href="Styles/linker.css" rel="stylesheet" type="text/css" />

<script language="javascript" type="text/javascript">//<![CDATA[
lang.Linker_enter_terms = '{% lang 'Linker_enter_terms' %}';
//]]></script>

<div id="ModalTitle">{% lang 'StoreLinker' %}</div>

<div id="ModalContent">
	<div id="ModalTabs" class="tabs">
		<ul class="tabnav">
			{% for tab in ['product','category','brand','page'] %}
				{% if tab in tabs %}
					<li><a href="#{{ tab }}_tab"><span>{% lang 'Linker_' ~ tab ~ '_tab' %}</span></a></li>
				{% endif %}
			{% endfor %}
		</ul>

		<div class="panel_wrapper StoreLinker">

			{% if 'product' in tabs %}
				<div id="product_tab" class="panel">
					<h3>{% lang 'Linker_link_product' %}</h3>
					<p>{% lang 'Linker_link_product_intro' %}</p>

					<fieldset>
						<legend>{% lang 'Linker_search_by_category' %}</legend>

						<div class="LinkList" id="ProductByCategoryList"></div>
					</fieldset>

					<fieldset>
						<legend>{% lang 'Linker_search_by_product' %}</legend>

						<input type="text" style="width: 100%;" id="productName" name="productName" onkeypress="StoreLinker.setSearchTimeout();"/>

						<div class="LinkList" id="ProductByKeywordList">{% lang 'Linker_enter_terms' %}</div>
					</fieldset>
				</div>
			{% endif %}

			{% if 'category' in tabs %}
				<div id="category_tab" class="panel">
					<h3>{% lang 'Linker_link_category' %}</h3>
					<p>{% lang 'Linker_link_category_intro' %}</p>

					<div class="LinkList" id="CategoryList" style="height: 400px;"></div>
				</div>
			{% endif %}

			{% if 'brand' in tabs %}
				<div id="brand_tab" class="panel">
					<h3>{% lang 'Linker_link_brand' %}</h3>
					<p>{% lang 'Linker_link_brand_intro' %}</p>

					<div class="LinkList" id="BrandList" style="height: 400px;"></div>
				</div>
			{% endif %}

			{% if 'page' in tabs %}
				<div id="page_tab" class="panel">
					<h3>{% lang 'Linker_link_page' %}</h3>
					<p>{% lang 'Linker_link_page_intro' %}</p>

					<div class="LinkList" id="PageList" style="height: 400px;"></div>
				</div>
			{% endif %}
		</div>
	</div>
</div>

<div id="ModalButtonRow">
	<input type="button" class="Button" value="{% lang 'Cancel' %}" onclick="$.iModal.close();" style="float: left;" />
	<input class="Submit" class="Submit" type="submit" value="{% lang 'Linker_insert' %}"  onclick="$.iModal.close();"  />
</div>

<script language="javascript" type="text/javascript">//<![CDATA[
(function($){
	var tabSelected = function(id){
		switch (id) {
			case 'product_tab':
				StoreLinker.load_list("ProductByCategoryList", "categories", "");
				break;

			case 'category_tab':
				StoreLinker.load_list('CategoryList', 'categories');
				break;

			case 'brand_tab':
				StoreLinker.load_list('BrandList', 'brands');
				break;

			case 'page_tab':
				StoreLinker.load_list('PageList', 'pages');
				break;
		}
	};

	$('#ModalTabs').tabs({ select: function(event, ui){
		tabSelected($(ui.panel).attr('id'));
	}});

	{# can't seem to get jquery-ui tabs to fire the tabsselect event when initialized, so we need to be able to call it here #}
	tabSelected('{{ tabs[0] }}_tab');
})(jQuery);
//]]></script>
