<?php

class ISC_INTELISIS_REPORT
{
	public function HandlePage(){
		if(!isset($GLOBALS['ISC_CLASS_CUSTOMER'])){
			$GLOBALS['ISC_CLASS_CUSTOMER'] = GetClass('ISC_CUSTOMER');
		}
		$custInfo = $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerInfo();
		if($custInfo == NULL){
			header(sprintf("location:%s/account.php", $GLOBALS['ShopPath']));
		}else{
			if(isset($_REQUEST['ReportName'])){
				$GLOBALS['ReportNameG'] = $_REQUEST['ReportName'];
				$todo = strtolower(trim($_REQUEST['ReportName']));
			}
			else {
				$GLOBALS['ReportNameG'] = '';
				$todo = '';
			}
			
			switch ($todo) {
				case 'customerservice':
					$GLOBALS['HideDatePickerReport'] = '';
					$this->CustomerService();
					break;
				case 'accountstatus':
					$GLOBALS['HideDatePickerReport'] = '';
					$this->AccountStatus();
					break;
				case 'orderstatus':
					$GLOBALS['HideDatePickerReport'] = '';
					$this->OrderStatus();
					break;
				case 'pendingorders':
					$GLOBALS['HideDatePickerReport'] = '';
					$this->PendingOrders();
					break;
				case 'productpurchases':
					$GLOBALS['HideDatePickerReport'] = '';
					$this->ProductPurchases();
					break;
				case 'orderintelisisstatus':
					$GLOBALS['HideDatePickerReport'] = '';
					$this->OrderIntelisisStatus();
					break;
				case 'reportdetail':
					$GLOBALS['HideDatePickerReport'] = 'none';
					$this->ReportDetail();
					break;
				default:
					$GLOBALS['HideDatePickerReport'] = 'none';
					$this->showReports();
					break;
			}
		}
	}
	
	public function showReports(){
		$GLOBALS['ReportContent'] = 
			'<ul>
			<li><a href="report.php?ReportName=CustomerService">Reportes de Atencion a Clientes</a></li>
			<li><a href="report.php?ReportName=AccountStatus">Estatus de Cuenta</a></li>
			<li><a href="report.php?ReportName=OrderStatus">Reporte de Pedidos</a></li>
			<li><a href="report.php?ReportName=PendingOrders">Compras Pendientes</a></li>
			<li><a href="report.php?ReportName=ProductPurchases">Compras por Articulo</a></li>
			<li><a href="report.php?ReportName=OrderIntelisisStatus">Detalle de Pedido en Intelisis</a></li>
			</ul>';
		
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle('Reportes');
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate('intelisis.report');
		$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
	}
	
	public function CustomerService(){
		
		if((isset($_POST['fromdate'])) && $_POST['fromdate'] != null && $_POST['todate'] != null){
			$fdate = strtotime($_POST['fromdate']);
			$tdate = strtotime($_POST['todate'])+86400;
		}else{
			$fdate = NULL;
			$tdate = NULL;
		}
		
		$IWS = new ISC_INTELISIS_WS_REPORT('AtencionClientesLista', $fdate, $tdate);
		if(!$IWS->prepareRequest()) return false;
		
		$GLOBALS['ReportContent'] = 'Customer Service';
		
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle('Reportes');
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate('intelisis.report');
		$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
	}
	
	public function AccountStatus(){
		
		if((isset($_POST['fromdate'])) && $_POST['fromdate'] != null && $_POST['todate'] != null){
			$fdate = strtotime($_POST['fromdate']);
			$tdate = strtotime($_POST['todate'])+86400;
		}else{
			$fdate = NULL;
			$tdate = NULL;
		}
		
		$IWS = new ISC_INTELISIS_WS_REPORT('EstadoCuenta', $fdate, $tdate);
		if(!$IWS->prepareRequest()) return false;
		
		$GLOBALS['ReportContent'] = 'Account Status';
		
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle('Reportes');
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate('intelisis.report');
		$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
	}
	
	public function OrderStatus(){
		
		if((isset($_POST['fromdate'])) && $_POST['fromdate'] != null && $_POST['todate'] != null){
			$fdate = strtotime($_POST['fromdate']);
			$tdate = strtotime($_POST['todate'])+86400;
		}else{
			$fdate = NULL;
			$tdate = NULL;
		}
		
		$IWS = new ISC_INTELISIS_WS_REPORT('ReportePedidos', $fdate, $tdate);
		if(!$IWS->prepareRequest()) return false;
		
		$GLOBALS['ReportContent'] = 'Order Status';
		
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle('Reportes');
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate('intelisis.report');
		$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
	}
	
	public function PendingOrders(){
		
		if((isset($_POST['fromdate'])) && $_POST['fromdate'] != null && $_POST['todate'] != null){
			$fdate = strtotime($_POST['fromdate']);
			$tdate = strtotime($_POST['todate'])+86400;
		}else{
			$fdate = NULL;
			$tdate = NULL;
		}
		
		$IWS = new ISC_INTELISIS_WS_REPORT('ComprasPendientes', $fdate, $tdate);
		if(!$IWS->prepareRequest()) return false;
		
		$GLOBALS['ReportContent'] = 'Pending Orders';
		
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle('Reportes');
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate('intelisis.report');
		$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
	}
	
	public function ProductPurchases(){
		
		if((isset($_POST['fromdate'])) && $_POST['fromdate'] != null && $_POST['todate'] != null){
			$fdate = strtotime($_POST['fromdate']);
			$tdate = strtotime($_POST['todate'])+86400;
		}else{
			$fdate = NULL;
			$tdate = NULL;
		}
		
		$IWS = new ISC_INTELISIS_WS_REPORT('ComprasPorArticulo', $fdate, $tdate);
		if(!$IWS->prepareRequest()) return false;
		
		$GLOBALS['ReportContent'] = 'Porduct Purchases';
		
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle('Reportes');
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate('intelisis.report');
		$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
	}
	
	public function OrderIntelisisStatus(){
		
		if((isset($_POST['fromdate'])) && $_POST['fromdate'] != null && $_POST['todate'] != null){
			$fdate = strtotime($_POST['fromdate']);
			$tdate = strtotime($_POST['todate'])+86400;
		}else{
			$fdate = NULL;
			$tdate = NULL;
		}
		
		$IWS = new ISC_INTELISIS_WS_REPORT('DetallePedidoIntelisis', $fdate, $tdate);
		if(!$IWS->prepareRequest()) return false;
		
		$GLOBALS['ReportContent'] = 'Order Intelisis Status';
		
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle('Reportes');
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate('intelisis.report');
		$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
	}
	
	public function ReportDetail(){
		$Id = $_REQUEST['id'];
		
		$IWS = new ISC_INTELISIS_WS_REPORT('AtencionClienteDetalle', NULL, NULL, $Id);
		if(!$IWS->prepareRequest()) return false;
		
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle('Reportes');
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate('intelisis.report');
		$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
	}
	
}