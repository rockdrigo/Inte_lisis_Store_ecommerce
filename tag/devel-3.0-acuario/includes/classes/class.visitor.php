<?php

	/**
	*	This class deals with tracking unique visitors which are then used in reporting
	*	from the Interspire Shopping Cart control panel. Only the date and number of visitors are
	*	stored in the unique_visitors table to minize server load.
	*/
	class ISC_VISITOR
	{
		/**
		 * Generate the <script src> tag to track visitors in the store.
		 *
		 * @return string The tracking javascript to be inserted in to pages.
		 */
		public function GetTrackingJavascript()
		{
			$script = "<script type=\"text/javascript\" src=\"".GetConfig('ShopPath')."/index.php?action=tracking_script\"></script>";
			return $script;
		}

		/**
		 * Output the actual Javascript that tracks the visitors by inserting a dummy image in to the page.
		 */
		public function OutputTrackingJavascript()
		{
			header('Content-type: text/javascript');

			$expires = 604800; //60 * 60 * 24 * 7;
			header("Pragma: public");
			header("Cache-control: public,maxage=" . $expires);
			header("Expires: " . gmdate("r", time() + $expires));

			echo "
				var img = new Image(1, 1);
				img.src = '".GetConfig('ShopPath')."/index.php?action=track_visitor&'+new Date().getTime();
				img.onload = function() { return true; };
			";
			exit;
		}

		/**
		 * Actually track a visitor.
		 */
		public function TrackVisitor()
		{
			$today_stamp = isc_gmmktime(0, 0, 0, isc_date("m"), isc_date("d"), isc_date("Y"));
			if(!isset($_COOKIE['STORE_VISITOR'])) {
				// We have a new visitor, let's track that.
				$query = sprintf("SELECT COUNT(uniqueid) AS num FROM [|PREFIX|]unique_visitors WHERE datestamp='%d'", $GLOBALS['ISC_CLASS_DB']->Quote($today_stamp));
				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
				$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

				if($row['num'] == 0) {
					// This person is the first to visit the site today, so track it
					$new_visitor = array(
						"datestamp" => $today_stamp,
						"numuniques" => 1
					);
					$GLOBALS['ISC_CLASS_DB']->InsertQuery("unique_visitors", $new_visitor);
				}
				else {
					// At least one person has visited the site today, just update the record
					$query = sprintf("UPDATE [|PREFIX|]unique_visitors SET numuniques=numuniques+1 WHERE datestamp='%d'", $today_stamp);

					// Run the query to update the number of unique visitors
					$GLOBALS['ISC_CLASS_DB']->Query($query);
				}

				// Set the tracking cookie for another 24 hours
				ISC_SetCookie("STORE_VISITOR", true, time()+86400);
			}
			header("Content-type: image/gif");
			echo base64_decode('R0lGODlhAQABALMAAAAAAIAAAACAAICAAAAAgIAAgACAgMDAwICAgP8AAAD/AP//AAAA//8A/wD//wBiZCH5BAEAAA8ALAAAAAABAAEAAAQC8EUAOw==');
			exit;
		}
	}