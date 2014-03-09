<?php

/*
 * Agregado por NES
 * 
 * Para Implementar este panel es necesario tener instalado y configurado el plugin jQuery FancyTransitions.
 * 
 * Ya con esto, es necesario crear un panel de nombre DefaultPageSlider.html en el directorio de Paneles del template, y adentro tiene que tener un Snippet llamado DefaultImagesSlderItem.html.
 * Tambien es necesario crear dicho Snippet en su directorio.
 * 
 * El Panel normalmente solo consiste del Snippet, con un <div> alrededor de clase SliderHome
 * El Snippet debe ser un elemento <img> con los siguientes atributos:
 *  * alt="%%GLOBAL_DefaultSliderImageAlt%%"
 *  * src="%%GLOBAL_DefaultSliderImage%%"
 * Despues del elemento de imagen, un elemento <a href="%%GLOBAL_DefaultSliderImageLink%%"></a> sin innerHTML
 */

	CLASS ISC_DEFAULTPAGESLIDER_PANEL extends PANEL
	{
		public function SetPanelSettings()
		{
			$query = 'SELECT * FROM [|PREFIX|]intelisis_slider_images ORDER BY Orden ASC';
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			
			$output = '';
			while($image = $GLOBALS['ISC_CLASS_DB']->Fetch($result))
			{
				$GLOBALS['DefaultSliderImage'] = GetConfig('ShopPath').DIRECTORY_SEPARATOR.GetConfig('ImageDirectory').DIRECTORY_SEPARATOR.'uploaded_images'.DIRECTORY_SEPARATOR.$image['Nombre'].$image['TipoArchivo'];
				$GLOBALS['DefaultSliderImageLink'] = $image['Liga'];
				if(trim($image['Descripcion']) == ''){
					$GLOBALS['DefaultSliderImageHideDesc'] = 'display: none';
				}
				else {
					$GLOBALS['DefaultSliderImageHideDesc'] = '';
				}
				$GLOBALS['DefaultSliderImageAlt'] = $image['Descripcion'];
				$output .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("DefaultImagesSliderItem");
			}
			
			$GLOBALS['SNIPPETS']['DefaultImagesSlider'] = $output;
		}
	}
