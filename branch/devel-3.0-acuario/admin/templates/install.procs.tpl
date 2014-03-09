/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;

DELIMITER //
DROP PROCEDURE IF EXISTS spAlterTable//
CREATE PROCEDURE `spAlterTable`(IN `vTabla` varchar(64), IN `vColumna` varchar(64), IN `vExtra` varchar(64)
			)
	LANGUAGE SQL
	NOT DETERMINISTIC
	CONTAINS SQL
	SQL SECURITY DEFINER
	COMMENT ''
BEGIN
DECLARE vBase varchar(64);
SET vBase = DATABASE();

IF NOT EXISTS(SELECT * FROM information_schema.`COLUMNS` WHERE TABLE_SCHEMA = vBase AND TABLE_NAME = vTabla AND COLUMN_NAME = vColumna)
THEN
SET @smnt1 = concat('ALTER TABLE ', vTabla,' ADD COLUMN ', vColumna,' ', vExtra);
PREPARE smt_alter FROM @smnt1;
EXECUTE smt_alter;
ELSE
SET @smnt1 = concat('ALTER TABLE ', vTabla, ' CHANGE COLUMN ', vColumna, ' ', vColumna, ' ', vExtra);
PREPARE smt_alter FROM @smnt1;
EXECUTE smt_alter;
END IF;

END//
DELIMITER ;

-- Dumping structure for procedure store_db.%%PREFIX%%spArtPrecio
DELIMITER //
DROP PROCEDURE IF EXISTS %%PREFIX%%spArtPrecio//
CREATE PROCEDURE %%PREFIX%%spArtPrecio
			(
			in pArticulo          varchar(20),
			in pCantidad          decimal(30,10),
			in pUnidadVenta	    varchar(50),
			in pPrecio            decimal(30,10),
			in pDescuento         decimal(30,10),
			in pVentaID           integer,
			in pSubCuenta         varchar(50),
			in pFechaEmision      datetime,
			in pAgente            varchar(10),
			in pMoneda            varchar(10), 
			in pTipoCambio        decimal(30,10),
			in pCondicion         varchar(50),
			in pAlmacen           varchar(10),
			in pProyecto          varchar(50),
			in pFormaEnvio        varchar(50),
			in pMov               varchar(20),
			in pContactoTipo      varchar(50),
			in pContratoTipo      varchar(50),
			in pEmpresa           varchar(50),
			in pRegion            varchar(50),
			in pSucursal          int        ,
			in pListaPreciosEsp   varchar(20),
			in pCliente           varchar(10),                
			in pPrecioConDescuento tinyint(1),
			in pGetListaPreciosCliente	tinyint(1),
			in pSucursalCliente			tinyint(1)
			)

BEGIN
  DECLARE vEnviarA						integer;
  DECLARE vPolitica						longtext;
  DECLARE vDescuentoMonto				decimal(30,10);
  DECLARE vDescuentoMontoPorcentaje		decimal(30,10);
  
  DELETE FROM %%PREFIX%%intelisis_PrecioD
	WHERE ID IN (SELECT ID
				FROM %%PREFIX%%intelisis_Precio
				WHERE Tipo IN ('Precio', 'Precio=Costo+[$]', '$ Descuento', '$ Descuento (Variable)')
				AND IFNULL(NivelArticulo, 0) = 0);
  DELETE FROM %%PREFIX%%intelisis_Precio
  	WHERE Tipo IN ('Precio', 'Precio=Costo+[$]', '$ Descuento', '$ Descuento (Variable)')
				AND IFNULL(NivelArticulo, 0) = 0;
    
  IF pGetListaPreciosCliente = 1 THEN
    SELECT 
	  IFNULL(IFNULL(NULLIF(isa.ListaPreciosEsp, ''), NULLIF(c.ListaPreciosEsp, '')), '(Precio Lista)')
      INTO
	  pListaPreciosEsp
	  FROM %%PREFIX%%intelisis_Cte c
	 INNER JOIN %%PREFIX%%intelisis_shipping_addresses isa 
        ON c.Cliente = isa.Cliente AND isa.IDEnviarA = pSucursalCliente
				WHERE c.Cliente = pCliente; 
  END IF;

  CALL %%PREFIX%%spPoliticaPreciosCalc (pFechaEmision, pAgente, pMoneda, pTipoCambio, pCondicion, pAlmacen, pProyecto, pFormaEnvio, pMov, pContactoTipo,pContratoTipo, pEmpresa, pRegion, pSucursal, pListaPreciosEsp, pCliente, pArticulo, pSubCuenta, pCantidad, pUnidadVenta, pPrecio, pDescuento, vPolitica, vDescuentoMonto);

  IF (IFNULL(vDescuentoMonto,0.0) > 0.0 AND IFNULL(pPrecio,0.0) > 0.0) THEN
    SET vDescuentoMontoPorcentaje = (vDescuentoMonto / (pPrecio * pCantidad)) * 100.0;
    SET pDescuento = IFNULL(pDescuento,0.0) + IFNULL(vDescuentoMontoPorcentaje,0.0);
  END IF;

  IF IFNULL(pPrecioConDescuento, 0) = 1 THEN
    SET pPrecio = pPrecio - (pPrecio * (IFNULL(pDescuento,0.0)/100.0));
    SET pDescuento = NULL;
  END IF;
	-- Agrego este SELECT para regresar los valores a PHP
	SELECT pPrecio AS 'Precio', pDescuento AS 'Descuento';
END// 
DELIMITER ;


-- Dumping structure for procedure store_db.%%PREFIX%%spPoliticaPreciosCalc
DELIMITER //
DROP PROCEDURE IF EXISTS `%%PREFIX%%spPoliticaPreciosCalc`//
CREATE PROCEDURE `%%PREFIX%%spPoliticaPreciosCalc`(IN `pFechaEmision` datetime, IN `pAgente` varchar(10), IN `pMoneda` varchar(10), IN `pTipoCambio` decimal(30,10), IN `pCondicion` varchar(50), IN `pAlmacen` varchar(10), IN `pProyecto` varchar(50), IN `pFormaEnvio` varchar(50), IN `pMov` varchar(20), IN `pServicioTipo` varchar(50), IN `pContratoTipo` varchar(50), IN `pEmpresa` varchar(50), IN `pRegion` varchar(50), IN `pSucursal` int, IN `pListaPreciosEsp` varchar(20), IN `pCliente` varchar(10), IN `pArticulo` varchar(20), IN `pSubCuenta` varchar(50), IN `pCantidad` decimal(30,10), IN `pUnidadVenta` varchar(50), INOUT `pPrecio` decimal(30,10), INOUT `pDescuento` decimal(30,10), INOUT `pPolitica` longtext, INOUT `pDescuentoMonto` decimal(30,10)
			)
BEGIN
  DECLARE vArtCat							varchar(50);
  DECLARE vArtGrupo						varchar(50);
  DECLARE vArtFam							varchar(50);
  DECLARE vArtAbc							varchar(1);
  DECLARE vFabricante						varchar(50);
  DECLARE vArtLinea						varchar(50);
  DECLARE vArtRama						varchar(20);
  	
  DECLARE vCteGrupo						varchar(50);
  DECLARE vCteCat							varchar(50);
  DECLARE vCteFam							varchar(50);
  DECLARE vCteZona						varchar(30);
	  
  DECLARE vTipo							varchar(50);
  DECLARE vNivel							varchar(50);
  DECLARE vNivelPolitica					varchar(50);
  DECLARE vCosto							decimal(30,10);
  DECLARE vTipoCosteo						varchar(20);
  DECLARE vPrecioLista					decimal(30,10); 
  DECLARE vPrecio2						decimal(30,10);
  DECLARE vPrecio3						decimal(30,10);
  DECLARE vPrecio4						decimal(30,10);
  DECLARE vPrecio5						decimal(30,10);
  DECLARE vPrecio6						decimal(30,10);
  DECLARE vPrecio7						decimal(30,10);
  DECLARE vPrecio8						decimal(30,10);
  DECLARE vPrecio9						decimal(30,10);
  DECLARE vPrecio10						decimal(30,10);
	  
  DECLARE vDescuentoAcum					decimal(30,10);
  DECLARE vPonderado						decimal(30,10);
  DECLARE vCalcDescuento					decimal(30,10);
      
  DECLARE vPrecioAcum						decimal(30,10);
  DECLARE vPrecioTemp						decimal(30,10);
  DECLARE vDescripcion					varchar(50);
  DECLARE vConVigencia					tinyint(1);
  DECLARE vFechaD							datetime;
  DECLARE vFechaA							datetime;
  DECLARE vPromocionExclusiva			tinyint(1);
  DECLARE vCalcDescuentoExclusivo		decimal(30,10);
  DECLARE vDescuentoExclusivo			decimal(30,10);
  DECLARE vPonderadoExclusivo			decimal(30,10);
  DECLARE vPromocionPromocion			tinyint(1);
  DECLARE vCalcDescuentoPromocion		decimal(30,10);
  DECLARE vDescuentoPromocion			decimal(30,10);
  DECLARE vPonderadoPromocion			decimal(30,10);
  DECLARE vPromocionParticular			tinyint(1);
  DECLARE vCalcDescuentoParticular		decimal(30,10);
  DECLARE vDescuentoParticular			decimal(30,10);
  DECLARE vPonderadoParticular			decimal(30,10);
  DECLARE vPromocionExclusivaAplicada	tinyint(1);
  DECLARE vPromocionPromocionAplicada	tinyint(1);
  DECLARE vPromocionParticularAplicada	tinyint(1); 
  DECLARE CursorTerminado INT DEFAULT 0;     

   -- Calcular Descuento en Porcentaje
   DECLARE crDescto CURSOR FOR    
    SELECT 
		pd.Monto,
		p.Nivel,
		p.Descripcion,
		p.FechaD,
		p.FechaA,
		p.ConVigencia
      FROM %%PREFIX%%intelisis_Precio p
     INNER JOIN %%PREFIX%%intelisis_PrecioD pd ON p.ID = pd.ID
                          AND pCantidad >= pd.Cantidad                          
     WHERE ((IFNULL(p.ConVigencia,0) = 0) OR (pFechaEmision BETWEEN p.FechaD AND p.FechaA))
       -- Condiciones de Propiedades de Articulo 
       AND ((IFNULL(p.NivelArticulo,0) = 0) OR (p.Articulo = pArticulo)) 
       AND ((IFNULL(p.NivelSubCuenta,0) = 0) OR (p.SubCuenta = pSubCuenta)) 
       AND ((IFNULL(p.NivelUnidadVenta,0) = 0) OR (p.UnidadVenta = pUnidadVenta)) 
       AND ((IFNULL(p.NivelArtCat,0) = 0) OR (p.ArtCat = vArtCat))
       AND ((IFNULL(p.NivelArtGrupo,0) = 0) OR (p.ArtGrupo = vArtGrupo)) 
       AND ((IFNULL(p.NivelArtFam,0) = 0) OR (p.ArtFam = vArtFam))
       AND ((IFNULL(p.NivelArtABC,0) = 0) OR (p.ArtAbc = vArtAbc))  
       AND ((IFNULL(p.NivelFabricante,0) = 0) OR (p.Fabricante = vFabricante))
       AND ((IFNULL(p.NivelArtLinea,0) = 0) OR (p.ArtLinea = vArtLinea)) 
       AND ((IFNULL(p.NivelArtRama,0) = 0) OR (p.ArtRama = vArtRama))
       -- Condiciones de Propiedades de Clientes
       AND ((IFNULL(p.NivelCliente,0) = 0) OR (p.Cliente = pCliente))
       AND ((IFNULL(p.NivelCteGrupo,0) = 0) OR (p.CteGrupo = vCteGrupo))
       AND ((IFNULL(p.NivelCteCat,0) = 0) OR (p.CteCat = vCteCat))
       AND ((IFNULL(p.NivelCteFam,0) = 0) OR (p.CteFam = vCteFam))
       AND ((IFNULL(p.NivelCteZona,0) = 0) OR (p.CteZona = vCteZona))
        -- Condiciones de Propiedades de la Factura
       AND ((IFNULL(p.NivelAgente,0) = 0) OR (p.Agente = pAgente))
       AND ((IFNULL(p.NivelMoneda,0) = 0) OR (p.Moneda = pMoneda))
       AND ((IFNULL(p.NivelCondicion,0) = 0) OR (p.Condicion = pCondicion))
       AND ((IFNULL(p.NivelAlmacen,0) = 0) OR (p.Almacen = pAlmacen))
       AND ((IFNULL(p.NivelProyecto,0) = 0) OR (p.Proyecto = pProyecto))
       AND ((IFNULL(p.NivelFormaEnvio,0) = 0) OR (p.FormaEnvio = pFormaEnvio))
       AND ((IFNULL(p.NivelMov,0) = 0) OR (p.Mov = pMov))
       AND ((IFNULL(p.NivelServicioTipo,0) = 0) OR (p.ServicioTipo = pServicioTipo))
       AND ((IFNULL(p.NivelContratoTipo,0) = 0) OR (p.ContratoTipo = pContratoTipo))
       AND ((IFNULL(p.NivelEmpresa,0) = 0) OR (p.Empresa = pEmpresa)) 
       AND ((IFNULL(p.NivelRegion,0) = 0) OR (p.Region = pRegion))
       AND ((IFNULL(p.NivelSucursal,0) = 0) OR (p.Sucursal = pSucursal))          
       AND ((IFNULL(p.ListaPrecios,'Todas') = 'Todas') OR (p.ListaPrecios = pListaPreciosEsp))
       AND p.Tipo = '% Descuento'
       AND p.Estatus = 'ACTIVA'
       AND pd.Cantidad = (SELECT MAX(Cantidad) 
                            FROM %%PREFIX%%intelisis_PrecioD pd2 
                           WHERE p.ID = pd2.ID
                             AND pCantidad >= pd2.Cantidad);
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET CursorTerminado = 1;

  SET vPromocionExclusiva  = 0;
  SET vPromocionPromocion  = 0;
  SET vPromocionParticular = 0;

  SET vPromocionExclusivaAplicada = 0;
  SET vPromocionPromocionAplicada = 0;
  SET vPromocionParticularAplicada = 0;
       
  -- Obtiene la información del articulo	    	  
  SELECT 
	a.Categoria,
	a.Grupo,
	a.Familia,
	a.ABC,
	a.Fabricante,
	a.Linea,
	a.Rama,
	a.PrecioLista,
	a.Precio2,
	a.Precio3,
	a.Precio4,
	a.Precio5,
	a.Precio6,
	a.Precio7,
	a.Precio8,
	a.Precio9,
	a.Precio10
	INTO
	vArtCat,
	vArtGrupo,
	vArtFam,
	vArtAbc,
	vFabricante,
	vArtLinea,
	vArtRama,
	vPrecioLista,
	vPrecio2,
	vPrecio3,
	vPrecio4,
	vPrecio5,
	vPrecio6,
	vPrecio7,
	vPrecio8,
	vPrecio9,
	vPrecio10
    FROM %%PREFIX%%intelisis_Art a 
   WHERE a.Articulo = pArticulo;
   
   -- Obtiene los agrupadores del cliente
   SELECT 
	c.Grupo,
	c.Categoria,
	c.Familia,
	c.Zona
	 INTO
	vCteGrupo,
	vCteCat,
	vCteFam,
	vCteZona
     FROM %%PREFIX%%intelisis_Cte c
    WHERE c.Cliente = pCliente;

  CALL %%PREFIX%%spPCGet(pSucursal, pEmpresa, pArticulo, pSubCuenta, pUnidadVenta, pMoneda, pTipoCambio, pListaPreciosEsp, vPrecioTemp, 0, 0, NULL, NULL);

   -- Calcular Descuento en Monto
   SET pDescuentoMonto = 0;
    SELECT 
      SUM(pd.Monto)
      INTO
      pDescuentoMonto
      FROM %%PREFIX%%intelisis_Precio p
     INNER JOIN %%PREFIX%%intelisis_PrecioD pd ON p.ID = pd.ID AND pCantidad >= pd.Cantidad                                                    
     WHERE ((IFNULL(p.ConVigencia,0) = 0) OR (pFechaEmision BETWEEN p.FechaD AND p.FechaA)) -- Verifica la vigencia
       -- Condiciones de Propiedades de Articulo 
       AND ((IFNULL(p.NivelArticulo,0) = 0) OR (p.Articulo = pArticulo)) -- Verifica que la politica sea para el articulo
       AND ((IFNULL(p.NivelSubCuenta,0) = 0) OR (p.SubCuenta = pSubCuenta)) -- Verifica que la politica sea para la opcion determinada
       AND ((IFNULL(p.NivelUnidadVenta,0) = 0) OR (p.UnidadVenta = pUnidadVenta)) -- Verifica que la politica sea para la unidad de venta especifica 
       AND ((IFNULL(p.NivelArtCat,0) = 0) OR (p.ArtCat = vArtCat)) -- Verifica que la politica sea para la categoria de articulo especifica
       AND ((IFNULL(p.NivelArtGrupo,0) = 0) OR (p.ArtGrupo = vArtGrupo)) -- Verifica que la politica sea para el grupo de articulo especifico
       AND ((IFNULL(p.NivelArtFam,0) = 0) OR (p.ArtFam = vArtFam)) -- Verifica que la politica sea para la familia de articulo especifica
       AND ((IFNULL(p.NivelArtABC,0) = 0) OR (p.ArtAbc = vArtAbc)) -- Verifica que la politica sea para el nivel de ABC de articulo especifico
       AND ((IFNULL(p.NivelFabricante,0) = 0) OR (p.Fabricante = vFabricante)) -- Verifica que la politica sea para el fabricante especifico
       AND ((IFNULL(p.NivelArtLinea,0) = 0) OR (p.ArtLinea = vArtLinea)) -- Verifica que la politica sea para la linea de articulo especifica 
       AND ((IFNULL(p.NivelArtRama,0) = 0) OR (p.ArtRama = vArtRama)) -- Verifica que la politica sea para la rama especifica
       -- Condiciones de Propiedades de Clientes
       AND ((IFNULL(p.NivelCliente,0) = 0) OR (p.Cliente = pCliente)) -- Verifica el cliente   
       AND ((IFNULL(p.NivelCteGrupo,0) = 0) OR (p.CteGrupo = vCteGrupo))  -- Verifica el grupo del cliente   
       AND ((IFNULL(p.NivelCteCat,0) = 0) OR (p.CteCat = vCteCat)) -- Verifica la categoria del cliente   
       AND ((IFNULL(p.NivelCteFam,0) = 0) OR (p.CteFam = vCteFam)) -- Verifica la familia del cliente   
       AND ((IFNULL(p.NivelCteZona,0) = 0) OR (p.CteZona = vCteZona)) -- Verifica la zona del cliente   
        -- Condiciones de Propiedades de la Factura
       AND ((IFNULL(p.NivelAgente,0) = 0) OR (p.Agente = pAgente)) -- Verifica el agente   
       AND ((IFNULL(p.NivelMoneda,0) = 0) OR (p.Moneda = pMoneda)) -- Verifica la moneda
       AND ((IFNULL(p.NivelCondicion,0) = 0) OR (p.Condicion = pCondicion)) -- Verifica la condicion de pago
       AND ((IFNULL(p.NivelAlmacen,0) = 0) OR (p.Almacen = pAlmacen)) -- Verifica el almacen
       AND ((IFNULL(p.NivelProyecto,0) = 0) OR (p.Proyecto = pProyecto)) -- Verifica el proyecto
       AND ((IFNULL(p.NivelFormaEnvio,0) = 0) OR (p.FormaEnvio = pFormaEnvio)) -- Verifica la forma de envio
       AND ((IFNULL(p.NivelMov,0) = 0) OR (p.Mov = pMov)) -- Verifica el movimiento
       AND ((IFNULL(p.NivelServicioTipo,0) = 0) OR (p.ServicioTipo = pServicioTipo)) -- Verifica el servicio
       AND ((IFNULL(p.NivelContratoTipo,0) = 0) OR (p.ContratoTipo = pContratoTipo)) -- Verifica el contrato
       AND ((IFNULL(p.NivelEmpresa,0) = 0) OR (p.Empresa = pEmpresa)) -- Verifica la empresa
       AND ((IFNULL(p.NivelRegion,0) = 0) OR (p.Region = pRegion)) -- Verifica la region
       AND ((IFNULL(p.NivelSucursal,0) = 0) OR (p.Sucursal = pSucursal)) -- Verifica la sucursal          
       AND ((IFNULL(p.ListaPrecios,'Todas') = 'Todas') OR (p.ListaPrecios = pListaPreciosEsp)) -- Verifica las listas de precios
       AND p.Tipo LIKE '$ Descuento%' -- Valida que las politicas se expresen en monto
       AND p.Estatus = 'ACTIVA' -- Valida que la politica este activa
       AND pd.Cantidad = (SELECT MAX(Cantidad) 
                            FROM %%PREFIX%%intelisis_PrecioD pd2 
                           WHERE p.ID = pd2.ID
                             AND pCantidad >= pd2.Cantidad); -- Solo obtiene las partidas de la politica donde la cantidad sea menor o igual a la cantidad vendida

  SET CursorTerminado = 0;
  OPEN crDescto;
  FETCH CrDescto INTO vDescuentoAcum, vNivel, vDescripcion, vFechaD, vFechaA, vConVigencia;	      
      
    -- Si es promocion, particulo o Exclusiva regresa el primer descuento pero segun yo deberÌa de estar ordenado de alguna forma
    -- SET vPromocionExclusiva = 0 --PROMOCIONEXCLUSIVA
    IF vNivel IN ('Exclusiva') THEN 
      SET vPromocionExclusivaAplicada = 1;
      SET vPromocionExclusiva = 1;
    ELSE
      IF vNivel IN ('Promocion') THEN
        SET vPromocionPromocionAplicada = 1;
        SET vPromocionPromocion = 1;
      ELSE
        IF vNivel IN ('Particular') THEN 
          SET vPromocionParticularAplicada = 1;
          SET vPromocionParticular = 1;
        END IF;  
      END IF;
    END IF; 

  
    -- Calcula el descuento y el complemento
    SET vPonderado = 100;
    SET vCalcDescuento = (vDescuentoAcum /100) * vPonderado;
    SET pDescuento = vCalcDescuento;
    SET vPonderado = vPonderado - vCalcDescuento;

    -- Calcula el descuento exclusivo y el complemento
    SET vPonderadoExclusivo = 100;
    SET vCalcDescuentoExclusivo = (vDescuentoAcum /100) * vPonderadoExclusivo;
    SET vDescuentoExclusivo = vCalcDescuentoExclusivo;
    SET vPonderadoExclusivo = vPonderadoExclusivo - vCalcDescuentoExclusivo;

    -- Calcula el descuento promocion y el complemento
    SET vPonderadoPromocion = 100;
    SET vCalcDescuentoPromocion = (vDescuentoAcum /100) * vPonderadoPromocion;
    SET vDescuentoPromocion = vCalcDescuentoPromocion;
    SET vPonderadoPromocion = vPonderadoPromocion - vCalcDescuentoPromocion;

    -- Calcula el descuento particular y el complemento
    SET vPonderadoParticular = 100;
    SET vCalcDescuentoParticular = (vDescuentoAcum /100) * vPonderadoParticular;
    SET vDescuentoParticular = vCalcDescuentoParticular;
    SET vPonderadoParticular = vPonderadoParticular - vCalcDescuentoParticular;


  -- WHILE @@FETCH_STATUS = 0 
  REPEAT 
    FETCH NEXT FROM CrDescto INTO vDescuentoAcum, vNivel, vDescripcion, vFechaD, vFechaA, vConVigencia;
    IF CursorTerminado <> 1 THEN
        IF vNivel IN ('Exclusiva') THEN 
          SET vPromocionExclusiva = 1;
        ELSE 
          IF vNivel IN ('Promocion') THEN
            SET vPromocionPromocion = 1;
          ELSE 
            IF vNivel IN ('Particular') THEN
              SET vPromocionParticular = 1;
            END IF;
          END IF;  
        END IF;

        SET vCalcDescuento = (vDescuentoAcum /100) * vPonderado;    
        SET pDescuento = pDescuento + vCalcDescuento;
        SET vPonderado = vPonderado - vCalcDescuento;

        IF (vNivel IN ('Siempre')) OR (vNivel IN ('Exclusiva') AND vPromocionExclusivaAplicada = 0) THEN
          SET vCalcDescuentoExclusivo = (vDescuentoAcum /100) * vPonderadoExclusivo;
          SET vDescuentoExclusivo = vDescuentoExclusivo + vCalcDescuentoExclusivo;
          SET vPonderadoExclusivo = vPonderadoExclusivo - vCalcDescuentoExclusivo;
          IF vNivel IN ('Exclusiva') THEN 
            SET vPromocionExclusivaAplicada = 1;
          END IF;
        ELSE
          IF (vNivel IN ('Siempre')) OR (vNivel IN ('Promocion') AND vPromocionPromocionAplicada = 0) THEN
            SET vCalcDescuentoPromocion = (vDescuentoAcum /100) * vPonderadoPromocion;
            SET vDescuentoPromocion = vDescuentoPromocion + vCalcDescuentoPromocion;
            SET vPonderadoPromocion = vPonderadoPromocion - vCalcDescuentoPromocion;
            IF vNivel IN ('Promocion') THEN 
              SET vPromocionPromocionAplicada = 1;
            END IF;
          ELSE
            IF (vNivel IN ('Siempre')) OR (vNivel IN ('Particular') AND vPromocionParticularAplicada = 0) THEN
              SET vCalcDescuentoParticular = (vDescuentoAcum /100) * vPonderadoParticular;
              SET vDescuentoParticular = vDescuentoParticular + vCalcDescuentoParticular;
              SET vPonderadoParticular = vPonderadoParticular - vCalcDescuentoParticular;
              IF vNivel IN ('Particular') THEN 
                SET vPromocionParticularAplicada = 1;            
			   END IF;
            END IF;
          END IF;
        END IF;
    END IF;
  UNTIL CursorTerminado = 1 END REPEAT;
  CLOSE crDescto;

  IF vPromocionPromocion = 1 THEN                                                          
    SET pDescuento = vDescuentoPromocion;  
  ELSE
    IF vPromocionPromocion = 0 AND vPromocionParticular = 1 THEN
      SET pDescuento = vDescuentoParticular; 
    ELSE
      IF vPromocionPromocion = 0 AND vPromocionParticular = 0 AND vPromocionExclusiva = 1 THEN
        SET pDescuento = vDescuentoExclusivo;
      END IF;
    END IF;
  END IF;

  -- Obtiene el costo del articulo
  CALL %%PREFIX%%spVerCosto(pSucursal, pEmpresa, NULL, pArticulo, pSubcuenta, pUnidadVenta, vTipoCosteo, pMoneda, pTipoCambio, vCosto, 0, NULL, NULL, NULL);

    DROP TEMPORARY TABLE IF EXISTS TPrecioTemp;
    CREATE TEMPORARY TABLE TPrecioTemp (Precio decimal(30,10) NOT NULL, Orden integer NOT NULL, Tipo varchar(23) NOT NULL, Nivel varchar(10));

    -- Cuando es por precio, calcula el precio minimo segun el tipo especifico y pone todo esto en una tabla temporal
    INSERT INTO TPrecioTemp (Precio, Orden, Tipo, Nivel) 
    SELECT CASE WHEN p.Tipo = 'Precio' THEN MIN(IFNULL(pd.Monto,0))
			 WHEN p.Tipo = 'Precio=Costo+[%]' THEN MIN(vCosto + (vCosto * (IFNULL(pd.Monto,0) / 100.00)))
			 WHEN p.Tipo = 'Precio=Costo+[$]' THEN MIN(vCosto + IFNULL(pd.Monto,0))
			 WHEN p.Tipo = 'Precio=Costo+[% margen]' THEN MIN(vCosto / (1 - (IFNULL(pd.Monto,0) / 100.00)))
			 WHEN p.Tipo = 'Precio=Costo*[Factor]' THEN MIN(vCosto * pd.Monto)
			 WHEN p.Tipo = 'Precio=Precio+[%]' THEN MIN(vPrecioTemp + (vPrecioTemp * (IFNULL(pd.Monto,0) / 100.00)))
			 WHEN p.Tipo = 'Precio=Precio Lista+[%]' THEN MIN(vPrecioLista + (vPrecioLista * (IFNULL(pd.Monto,0) / 100.00)))
			 WHEN p.Tipo = 'Precio=Precio 2+[%]' THEN MIN(vPrecio2 + (vPrecio2 * (IFNULL(pd.Monto,0) / 100.00)))
			 WHEN p.Tipo = 'Precio=Precio 3+[%]' THEN MIN(vPrecio3 + (vPrecio3 * (IFNULL(pd.Monto,0) / 100.00)))
			 WHEN p.Tipo = 'Precio=Precio 4+[%]' THEN MIN(vPrecio4 + (vPrecio4 * (IFNULL(pd.Monto,0) / 100.00)))
			 WHEN p.Tipo = 'Precio=Precio 5+[%]' THEN MIN(vPrecio5 + (vPrecio5 * (IFNULL(pd.Monto,0) / 100.00)))
			 WHEN p.Tipo = 'Precio=Precio 6+[%]' THEN MIN(vPrecio6 + (vPrecio6 * (IFNULL(pd.Monto,0) / 100.00)))
			 WHEN p.Tipo = 'Precio=Precio 7+[%]' THEN MIN(vPrecio7 + (vPrecio7 * (IFNULL(pd.Monto,0) / 100.00)))
			 WHEN p.Tipo = 'Precio=Precio 8+[%]' THEN MIN(vPrecio8 + (vPrecio8 * (IFNULL(pd.Monto,0) / 100.00)))
			 WHEN p.Tipo = 'Precio=Precio 9+[%]' THEN MIN(vPrecio9 + (vPrecio9 * (IFNULL(pd.Monto,0) / 100.00)))
			 WHEN p.Tipo = 'Precio=Precio 10+[%]' THEN MIN(vPrecio10 + (vPrecio10 * (IFNULL(pd.Monto,0) / 100.00)))
	            END,
	   CASE WHEN p.Tipo = 'Precio' THEN 1
			WHEN p.Tipo = 'Precio=Costo+[%]' THEN 2
			WHEN p.Tipo = 'Precio=Costo+[$]' THEN 3
			WHEN p.Tipo = 'Precio=Costo+[% margen]' THEN 4
			WHEN p.Tipo = 'Precio=Costo*[Factor]' THEN 5
			WHEN p.Tipo = 'Precio=Precio+[%]' THEN 6
			WHEN p.Tipo = 'Precio=Precio Lista+[%]' THEN 7
			WHEN p.Tipo = 'Precio=Precio 2+[%]' THEN 8
			WHEN p.Tipo = 'Precio=Precio 3+[%]' THEN 9
			WHEN p.Tipo = 'Precio=Precio 4+[%]' THEN 10
			WHEN p.Tipo = 'Precio=Precio 5+[%]' THEN 11
			WHEN p.Tipo = 'Precio=Precio 6+[%]' THEN 12
			WHEN p.Tipo = 'Precio=Precio 7+[%]' THEN 13
			WHEN p.Tipo = 'Precio=Precio 8+[%]' THEN 14
			WHEN p.Tipo = 'Precio=Precio 9+[%]' THEN 15
			WHEN p.Tipo = 'Precio=Precio 10+[%]' THEN 16
		    ELSE 99999999
		    END,
		    p.Tipo,
		    p.Nivel
      FROM %%PREFIX%%intelisis_Precio p
     INNER JOIN %%PREFIX%%intelisis_PrecioD pd ON p.ID = pd.ID
                          AND pCantidad >= pd.Cantidad                          
     WHERE ((IFNULL(p.ConVigencia,0) = 0) OR (pFechaEmision BETWEEN p.FechaD AND p.FechaA))
       -- Condiciones de Propiedades de Articulo 
       AND ((IFNULL(p.NivelArticulo,0) = 0) OR (p.Articulo = pArticulo)) 
       AND ((IFNULL(p.NivelSubCuenta,0) = 0) OR (p.SubCuenta = pSubCuenta)) 
       AND ((IFNULL(p.NivelUnidadVenta,0) = 0) OR (p.UnidadVenta = pUnidadVenta)) 
       AND ((IFNULL(p.NivelArtCat,0) = 0) OR (p.ArtCat = vArtCat))
       AND ((IFNULL(p.NivelArtGrupo,0) = 0) OR (p.ArtGrupo = vArtGrupo)) 
       AND ((IFNULL(p.NivelArtFam,0) = 0) OR (p.ArtFam = vArtFam))
       AND ((IFNULL(p.NivelArtABC,0) = 0) OR (p.ArtAbc = vArtAbc))  
       AND ((IFNULL(p.NivelFabricante,0) = 0) OR (p.Fabricante = vFabricante))
       AND ((IFNULL(p.NivelArtLinea,0) = 0) OR (p.ArtLinea = vArtLinea)) 
       AND ((IFNULL(p.NivelArtRama,0) = 0) OR (p.ArtRama = vArtRama))
       -- Condiciones de Propiedades de Clientes
       AND ((IFNULL(p.NivelCliente,0) = 0) OR (p.Cliente = pCliente))
       AND ((IFNULL(p.NivelCteGrupo,0) = 0) OR (p.CteGrupo = vCteGrupo))
       AND ((IFNULL(p.NivelCteCat,0) = 0) OR (p.CteCat = vCteCat))
       AND ((IFNULL(p.NivelCteFam,0) = 0) OR (p.CteFam = vCteFam))
       AND ((IFNULL(p.NivelCteZona,0) = 0) OR (p.CteZona = vCteZona))
        -- Condiciones de Propiedades de la Factura
       AND ((IFNULL(p.NivelAgente,0) = 0) OR (p.Agente = pAgente))
       AND ((IFNULL(p.NivelMoneda,0) = 0) OR (p.Moneda = pMoneda))
       AND ((IFNULL(p.NivelCondicion,0) = 0) OR (p.Condicion = pCondicion))
       AND ((IFNULL(p.NivelAlmacen,0) = 0) OR (p.Almacen = pAlmacen))
       AND ((IFNULL(p.NivelProyecto,0) = 0) OR (p.Proyecto = pProyecto))
       AND ((IFNULL(p.NivelFormaEnvio,0) = 0) OR (p.FormaEnvio = pFormaEnvio))
       AND ((IFNULL(p.NivelMov,0) = 0) OR (p.Mov = pMov))
       AND ((IFNULL(p.NivelServicioTipo,0) = 0) OR (p.ServicioTipo = pServicioTipo))
       AND ((IFNULL(p.NivelContratoTipo,0) = 0) OR (p.ContratoTipo = pContratoTipo))
       AND ((IFNULL(p.NivelEmpresa,0) = 0) OR (p.Empresa = pEmpresa)) 
       AND ((IFNULL(p.NivelRegion,0) = 0) OR (p.Region = pRegion))
       AND ((IFNULL(p.NivelSucursal,0) = 0) OR (p.Sucursal = pSucursal))          
       AND ((IFNULL(p.ListaPrecios,'Todas') = 'Todas') OR (p.ListaPrecios = pListaPreciosEsp))
       AND p.Tipo LIKE ('Precio%')
       AND p.Estatus = 'ACTIVA'
       AND pd.Cantidad = (SELECT MAX(Cantidad) 
                            FROM %%PREFIX%%intelisis_PrecioD pd2 
                           WHERE p.ID = pd2.ID
                             AND pCantidad >= pd2.Cantidad)
   GROUP BY p.Tipo,
	    p.Nivel
	--  No hay ninguna columna Orden en las tablas Precio y PrecioD
   /*ORDER BY Orden*/;
   
   SELECT 
       Precio,
		Nivel
     INTO
       pPrecio,
       vNivelPolitica
     FROM TPrecioTemp
   ORDER BY Orden
   LIMIT 0, 1;
   
   IF vNivelPolitica = 'Exclusiva' THEN
     SET pDescuento = 0;
   END IF;
   
END//
DELIMITER ;

DELIMITER //
DROP PROCEDURE IF EXISTS %%PREFIX%%spMoneda//
CREATE PROCEDURE %%PREFIX%%spMoneda(in Accion varchar(20), in MovMoneda varchar(10), in MovTipoCambio decimal(30,6), in CuentaMoneda varchar(10), inout CuentaFactor decimal(30,6), inout CuentaTipoCambio decimal(30,6), inout Ok integer, in Modulo varchar(5), in ModuloID integer)
BEGIN
  DECLARE TipoCambioBase		decimal(30,6);
  DECLARE ToleranciaBase		decimal(30,6);
  DECLARE Minimo				DECIMAL(30,6);
  DECLARE Maximo				DECIMAL(30,6);

  SELECT CuentaFactor = 1.0, CuentaTipoCambio = IFNULL(CuentaTipoCambio,0.0);

  IF IFNULL(MovTipoCambio,0.0) = 0.0 THEN SELECT Ok = 30140; 
  ELSEIF MovMoneda IS NULL THEN SELECT Ok = 30040; 
  ELSEIF CuentaMoneda IS NULL THEN SELECT Ok = 30050; 
  ELSEIF UPPER(MovMoneda) <> UPPER(CuentaTipoCambio) THEN
    IF Accion = 'CANCELAR' AND CuentaTipoCambio <> 0.0 THEN
	  SELECT CuentaTipoCambio = CuentaTipoCambio;
	ELSE
      SELECT CuentaTipoCambio = 1.0;
	  SELECT 
	      IFNULL(TipoCambio,1.0)
		INTO 
		  CuentaTipoCambio
		FROM %%PREFIX%%intelisis_Mon
	   WHERE Moneda = CuentaMoneda;
	END IF;
    SELECT CuentaFactor = CuentaTipoCambio / NULLIF(MovTipoCambio,0.0);
  ELSE
    SELECT CuentaTipoCambio = MovTipoCambio;
  END IF;

  IF Accion <> 'CANCELAR' THEN
    SELECT
      TipoCambio,
      Tolerancia
      INTO
      TipoCambioBase,
      ToleranciaBase
      FROM %%PREFIX%%intelisis_Mon
     WHERE Moneda = MovMoneda;

    SELECT Minimo = TipoCambioBase * (1 - (ToleranciaBase/100.0)),
           Maximo = TipoCambioBase * (1 - (ToleranciaBase/100.0));

    IF MovTipoCambio < Minimo THEN SELECT Ok = 35080;
    ELSEIF MovTipoCambio > Maximo THEN SELECT Ok = 35090;
	END IF;
  END IF;    
      
END//
DELIMITER ;

DELIMITER //
DROP PROCEDURE IF EXISTS %%PREFIX%%spPCGet//
CREATE PROCEDURE %%PREFIX%%spPCGet(in pSucursal integer, in pEmpresa varchar(5), in pArticulo varchar(20), in pSubCuenta varchar(50), in pUnidad varchar(50), in pMovMoneda varchar(10), in pMovTipoCambio decimal(30,6), in pLista varchar(20), inout pPrecio decimal(30,6), in pVerResultado tinyint(1), in pPrecioOriginal tinyint(1), in pProveedor varchar(10), in pSucursalEsp integer)
BEGIN
  DECLARE vArtMoneda				varchar(10);
  DECLARE vArtFactor				decimal(30,6);
  DECLARE vArtTipoCambio			decimal(30,6);
  DECLARE vOk						integer;

  IF pVerResultado = NULL THEN SET pVerResultado = 0; END IF;
  IF pPrecioOriginal = NULL THEN SET pVerResultado = 0; END IF;
  IF pProveedor = '' THEN SET pProveedor = NULL; END IF;

  SET pPrecio = NULL, vArtMoneda = NULL, vOk = NULL, pSubCuenta = NULLIF(RTRIM(NULLIF(pSubCuenta,'0')),''), pUnidad = NULLIF(RTRIM(pUnidad),'');

/* NES pPrecioOriginal NUNCA va a ser uno, spPCGet es llamado siempre con 0
  IF pPrecioOriginal = 1 THEN
    CALL %%PREFIX%%spPCGetOriginal(pEmpresa, pArticulo, pSubCuenta, pUnidad, pMovMoneda, pMovTipoCambio, pLista, pPrecio);
  END IF;
  */

  IF pLista NOT IN ('(Ultimo Costo)', '(Costo Promedio)', '(Costo Estandar)', '(Costo Reposicion)', '(Costo Proveedor)', '(Ultimo Costo Prov)') THEN
    IF pUnidad IS NOT NULL THEN
      IF pSubCuenta IS NOT NULL THEN
        SELECT
          lpsu.Precio,
		  lpsu.Moneda
          INTO
          pPrecio,
          vArtMoneda
          FROM %%PREFIX%%intelisis_ListaPreciosSubUnidad lpsu
         WHERE lpsu.Lista = pLista
           AND lpsu.Moneda = pMovMoneda
           AND lpsu.Articulo = pArticulo
           AND lpsu.SubCuenta = pSubCuenta
           AND lpsu.Unidad = pUnidad;
      ELSE
        SELECT
          lpdu.Precio,
		  lpdu.Moneda
          INTO
          pPrecio,
          vArtMoneda
          FROM %%PREFIX%%intelisis_ListaPreciosDUnidad lpdu
         WHERE lpdu.Lista = pLista
           AND lpdu.Moneda = pMovMoneda
           AND lpdu.Articulo = pArticulo
           AND lpdu.SubCuenta = pSubCuenta
           AND lpdu.Unidad = pUnidad;  
      END IF;
	  IF pPrecio IS NULL AND pSubCuenta IS NOT NULL THEN
        SELECT
          lps.Precio,
		  lps.Moneda
          INTO
          pPrecio,
          vArtMoneda
          FROM %%PREFIX%%intelisis_ListaPreciosSub lps
         WHERE lps.Lista = pLista
           AND lps.Moneda = pMovMoneda
           AND lps.Articulo = pArticulo
           AND lps.SubCuenta = pSubCuenta
           AND lps.Unidad = pUnidad;          
      END IF;
    END IF;
  END IF;


  IF pPrecio IS NULL THEN
    IF SUBSTRING(pLista, 1, 1) = '(' THEN
      IF     pLista = '(Precio Lista)'  THEN SELECT a.MonedaPrecio, a.PrecioLista  INTO vArtMoneda, pPrecio FROM %%PREFIX%%intelisis_Art a WHERE a.Articulo = pArticulo; 
      ELSEIF pLista = '(Precio 2)'      THEN SELECT a.MonedaPrecio, a.Precio2      INTO vArtMoneda, pPrecio FROM %%PREFIX%%intelisis_Art a WHERE a.Articulo = pArticulo; 
      ELSEIF pLista = '(Precio 3)'      THEN SELECT a.MonedaPrecio, a.Precio3      INTO vArtMoneda, pPrecio FROM %%PREFIX%%intelisis_Art a WHERE a.Articulo = pArticulo; 
      ELSEIF pLista = '(Precio 4)'      THEN SELECT a.MonedaPrecio, a.Precio4      INTO vArtMoneda, pPrecio FROM %%PREFIX%%intelisis_Art a WHERE a.Articulo = pArticulo; 
      ELSEIF pLista = '(Precio 5)'      THEN SELECT a.MonedaPrecio, a.Precio5      INTO vArtMoneda, pPrecio FROM %%PREFIX%%intelisis_Art a WHERE a.Articulo = pArticulo; 
      ELSEIF pLista = '(Precio 6)'      THEN SELECT a.MonedaPrecio, a.Precio6      INTO vArtMoneda, pPrecio FROM %%PREFIX%%intelisis_Art a WHERE a.Articulo = pArticulo; 
      ELSEIF pLista = '(Precio 7)'      THEN SELECT a.MonedaPrecio, a.Precio7      INTO vArtMoneda, pPrecio FROM %%PREFIX%%intelisis_Art a WHERE a.Articulo = pArticulo; 
      ELSEIF pLista = '(Precio 8)'      THEN SELECT a.MonedaPrecio, a.Precio8      INTO vArtMoneda, pPrecio FROM %%PREFIX%%intelisis_Art a WHERE a.Articulo = pArticulo; 
      ELSEIF pLista = '(Precio 9)'      THEN SELECT a.MonedaPrecio, a.Precio9      INTO vArtMoneda, pPrecio FROM %%PREFIX%%intelisis_Art a WHERE a.Articulo = pArticulo; 
      ELSEIF pLista = '(Precio 10)'     THEN SELECT a.MonedaPrecio, a.Precio10     INTO vArtMoneda, pPrecio FROM %%PREFIX%%intelisis_Art a WHERE a.Articulo = pArticulo; 
      ELSEIF pLista = '(Precio Minimo)' THEN SELECT a.MonedaPrecio, a.PrecioMinimo INTO vArtMoneda, pPrecio FROM %%PREFIX%%intelisis_Art a WHERE a.Articulo = pArticulo; 
      ELSEIF pLista = '(Incentivo)'     THEN SELECT a.MonedaPrecio, a.Incentivo    INTO vArtMoneda, pPrecio FROM %%PREFIX%%intelisis_Art a WHERE a.Articulo = pArticulo; 
      /* Necesitamos esto? spPCGet solo nos puede mandar lo de arriba
      ELSE
        SELECT MonedaCosto INTO vArtMoneda FROM %%PREFIX%%intelisis_Art WHERE Articulo = pArticulo;
        IF pSubCuenta IS NULL THEN
          IF     pLista = '(Ultimo Costo)'     THEN SELECT a.UltimoCosto     INTO pPrecio FROM %%PREFIX%%intelisis_ArtCosto a WHERE a.Sucursal = pSucursal AND a.Empresa = pEmpresa AND a.Articulo = pArticulo;           
          ELSEIF pLista = '(Costo Promedio)'   THEN SELECT a.CostoPromedio   INTO pPrecio FROM %%PREFIX%%intelisis_ArtCosto a WHERE a.Sucursal = pSucursal AND a.Empresa = pEmpresa AND a.Articulo = pArticulo;           
          ELSEIF pLista = '(Costo Estandar)'   THEN SELECT a.CostoEstandar   INTO pPrecio FROM %%PREFIX%%intelisis_Art a      WHERE a.Articulo = pArticulo;
          ELSEIF pLista = '(Costo Reposicion)' THEN SELECT a.CostoReposicion INTO pPrecio FROM %%PREFIX%%intelisis_Art a      WHERE a.Articulo = pArticulo; 
          ELSEIF pLista IN ('(Costo Proveedor)', '(Ultimo Costo Prov)') THEN
            IF pSucursalEsp IS NULL THEN
              IF     pLista = '(Costo Proveedor)'   THEN SELECT a.CostoAutorizado INTO pPrecio FROM %%PREFIX%%intelisis_ArtProv a WHERE a.Articulo = pArticulo AND IFNULL(a.SubCuenta,'') = '' AND a.Proveedor = pProveedor;
              ELSEIF pLista = '(Ultimo Costo Prov)' THEN SELECT a.UltimoCosto     INTO pPrecio FROM %%PREFIX%%intelisis_ArtProv a WHERE a.Articulo = pArticulo AND IFNULL(a.SubCuenta,'') = '' AND a.Proveedor = pProveedor; 
              END IF;
            ELSE
              IF     pLista = '(Costo Proveedor)'   THEN SELECT a.CostoAutorizado INTO pPrecio FROM %%PREFIX%%intelisis_ArtProvSucursal a WHERE a.Articulo = pArticulo AND a.Proveedor = pProveedor AND a.Sucursal = pSucursalEsp; 
              ELSEIF pLista = '(Ultimo Costo Prov)' THEN SELECT a.UltimoCosto     INTO pPrecio FROM %%PREFIX%%intelisis_ArtProvSucursal a WHERE a.Articulo = pArticulo AND a.Proveedor = pProveedor AND a.Sucursal = pSucursalEsp; 
              END IF;
            END IF;
          END IF;         
        ELSE
          IF     pLista = '(Ultimo Costo)'    THEN SELECT a.UltimoCosto     INTO pPrecio FROM %%PREFIX%%intelisis_ArtSubCosto a WHERE a.Sucursal = pSucursal AND a.Empresa = pEmpresa AND a.Articulo = pArticulo AND IFNULL(a.SubCuenta,'') = IFNULL(pSubCuenta,'');
          ELSEIF pLista = '(Costo Promedio)'  THEN SELECT a.CostoPromedio   INTO pPrecio FROM %%PREFIX%%intelisis_ArtSubCosto a WHERE a.Sucursal = pSucursal AND a.Empresa = pEmpresa AND a.Articulo = pArticulo AND IFNULL(a.SubCuenta,'') = IFNULL(pSubCuenta,'');
          ELSEIF pLista = '(Costo Estandar)'  THEN SELECT a.CostoEstandar   INTO pPrecio FROM %%PREFIX%%intelisis_ArtSub a      WHERE a.Articulo = pArticulo AND IFNULL(a.SubCuenta,'') = IFNULL(pSubCuenta,'');
          ELSEIF pLista = '(Costo Proveedor)' THEN SELECT a.CostoReposicion INTO pPrecio FROM %%PREFIX%%intelisis_ArtSub a      WHERE a.Articulo = pArticulo AND IFNULL(a.SubCuenta,'') = IFNULL(pSubCuenta,'');
          ELSE
            IF pSucursalEsp IS NULL THEN
              IF     pLista = '(Costo Proveedor)'   THEN SELECT a.CostoAutorizado INTO pPrecio FROM %%PREFIX%%intelisis_ArtProv WHERE a.Articulo = pArticulo AND IFNULL(a.SubCuenta,'') = IFNULL(pSubCuenta,'') AND a.Proveedor = pProveedor;
              ELSEIF pLista = '(Ultimo Costo Prov)' THEN SELECT a.UltimoCosto     INTO pPrecio FROM %%PREFIX%%intelisis_ArtProv WHERE a.Articulo = pArticulo AND IFNULL(a.SubCuenta,'') = IFNULL(pSubCuenta,'') AND a.Proveedor = pProveedor;
              END IF;
            ELSE
              IF     pLista = '(Costo Proveedor)'   THEN SELECT a.CostoAutorizado INTO pPrecio FROM %%PREFIX%%intelisis_ArtProvSucursal WHERE a.Articulo = pArticulo AND IFNULL(a.SubCuenta,'') = IFNULL(pSubCuenta,'') AND a.Proveedor = pProveedor AND a.Sucursal = pSucursalEsp; 
              ELSEIF pLista = '(Ultimo Costo Prov)' THEN SELECT a.UltimoCosto     INTO pPrecio FROM %%PREFIX%%intelisis_ArtProvSucursal WHERE a.Articulo = pArticulo AND IFNULL(a.SubCuenta,'') = IFNULL(pSubCuenta,'') AND a.Proveedor = pProveedor AND a.Sucursal = pSucursalEsp; 
              END IF;
            END IF;
          END IF;
		END IF;
		Termina el "Necesitamos..." */
      END IF;
    ELSE
      SELECT a.MonedaPrecio INTO vArtMoneda FROM %%PREFIX%%intelisis_Art a WHERE a.Articulo = pArticulo;  
	  IF NULLIF(pSubCuenta,'') IS NULL THEN
        SELECT SQL_CALC_FOUND_ROWS l.Precio INTO pPrecio FROM %%PREFIX%%intelisis_ListaPreciosD l WHERE l.Lista = pLista AND l.Moneda = pMovMoneda AND l.Articulo = pArticulo;
      ELSE
        SELECT SQL_CALC_FOUND_ROWS l.Precio INTO pPrecio FROM %%PREFIX%%intelisis_ListaPreciosSub l WHERE l.Lista = pLista AND l.Moneda = pMovMoneda AND l.Articulo = pArticulo AND l.SubCuenta = pSubCuenta; 
      END IF;
      IF FOUND_ROWS() = 0 THEN
        SELECT a.MonedaPrecio INTO vArtMoneda FROM %%PREFIX%%intelisis_Art a WHERE a.Articulo = pArticulo;
        IF NULLIF(pSubCuenta,'') IS NULL THEN
          SELECT l.Precio INTO pPrecio FROM %%PREFIX%%intelisis_ListaPreciosD l WHERE l.Lista = pLista AND l.Moneda = vArtMoneda AND l.Articulo = pArticulo;
        ELSE
          SELECT l.Precio INTO pPrecio FROM %%PREFIX%%intelisis_ListaPreciosSub l WHERE l.Lista = pLista AND l.Moneda = vArtMoneda AND l.Articulo = pArticulo AND IFNULL(l.SubCuenta,'') = IFNULL(pSubCuenta,'');
        END IF;
      END IF;
    END IF;
  END IF;

  IF vArtMoneda <> pMovMoneda AND vArtMoneda IS NOT NULL THEN
    SET pPrecio = pPrecio * (SELECT IFNULL(m.TipoCambio,1.0) FROM %%PREFIX%%intelisis_Mon m WHERE m.Moneda = vArtMoneda) / pMovTipoCambio;
  END IF;

  IF pVerResultado = 1 THEN 
    SELECT pPrecio as 'Importe';
  END IF;

END//
DELIMITER ;

DELIMITER //
DROP PROCEDURE IF EXISTS %%PREFIX%%spVerCosto//
CREATE PROCEDURE %%PREFIX%%spVerCosto (
			IN vSucursal	int,
         IN vEmpresa	char(5),
			IN vProveedor	char(10),
			IN vArticulo	char(20),
			IN vSubCuenta	varchar(50),
			IN vMovUnidad	varchar(50),
			IN vCual		char(20),
			IN vMovMoneda	char(10),
			IN vMovTipoCambio	float,
      INOUT vMovCosto 	decimal(30,10),
			IN vConReturn      smallint,
			IN vPrecio		decimal(30,10),
			IN vModulo		char(5),
			IN vModuloID	int)
spVerCosto_label: BEGIN
DECLARE vCostoNivelOpcion		bit;
DECLARE vCfgMultiUnidadesNivel	char(20);
DECLARE vUltCostoProv		smallint;
DECLARE vArtMoneda			char(10);
DECLARE vArtTipo			varchar(20);
DECLARE vUltCosto			decimal(30,10);
DECLARE vCosto			decimal(30,10);
DECLARE vUltimo			decimal(30,10);
DECLARE vUltimoSinGastos		decimal(30,10);
DECLARE vEstandar			decimal(30,10);
DECLARE vReposicion			decimal(30,10);
DECLARE vPromedio			decimal(30,10);
DECLARE vArtFactor			float;
DECLARE vArtTipoCambio		float;
DECLARE vUnidadFactor		float;
DECLARE vMargen			float;
DECLARE vDecimales			int;
DECLARE vOk				int;
DECLARE vCostoOk			smallint;
DECLARE vMensaje			varchar(255);
DECLARE vUltimoCosto		decimal(30,10);
DECLARE vFechaUltimaCompra		datetime;
DECLARE vUltimaCotizacion		decimal(30,10);
DECLARE vFechaCotizacion		datetime;
DECLARE vSugerirCostoArtServicio	varchar(20);
DECLARE vImpuesto1			float;
DECLARE vImpuesto2			float;
DECLARE vImpuesto3			float;
DECLARE vJuntarImpuestos		float;
DECLARE vCfgImpInc			smallint;	
DECLARE vCfgPrecioMoneda		smallint;
DECLARE vRedondeoMonetarios		int;
DECLARE vImpuesto2Info		smallint;
DECLARE vImpuesto3Info		smallint;
DECLARE vTipoCambioBase	float;
DECLARE vToleranciaBase	float;
DECLARE vMinimo		float;
DECLARE vMaximo		float;

SET vUltCosto = vMovCosto, 
 vSubCuenta = NULLIF(NULLIF(RTRIM(vSubCuenta), ''), '0'), 
 vUltCostoProv = 0, 
 vProveedor = NULLIF(NULLIF(RTRIM(vProveedor), ''), '0'), 
 vMovUnidad = NULLIF(NULLIF(RTRIM(vMovUnidad), ''), '0'), 
 vCostoOk = 0;

-- Inicializar Variables
SET vArtFactor = 1.0, vUnidadFactor = 1.0, vCosto = NULL, vMovCosto = NULL, vCual = NULLIF(UPPER(RTRIM(vCual)), '');
/*
SELECT vCostoNivelOpcion        = CosteoNivelSubCuenta, 
   vSugerirCostoArtServicio = IFNULL(NULLIF(RTRIM(UPPER(SugerirCostoArtServicio)), ''), 'NO'),
   vCfgImpInc		    = VentaPreciosImpuestoIncluido
FROM EmpresaCfg 
WHERE Empresa = vEmpresa;
*/
SET vCostoNivelOpcion = 1;
SET vSugerirCostoArtServicio = 'NO';
SET vCfgImpInc = 1;
   
/*
		Problemas con esto.
		vSugerirCostoArtServicio puede ser (No, Estandar, Reposicion, Margen)

*/
IF vSugerirCostoArtServicio <> 'NO'
THEN
	SELECT UPPER(Tipo) INTO vArtTipo FROM %%PREFIX%%intelisis_Art WHERE Articulo = vArticulo;
	IF vArtTipo IN ('SERVICIO', 'JUEGO')
	THEN
		SET vCual = vSugerirCostoArtServicio;
	END IF;
END IF;
	-- Quito IF @Proveedor IS NOT NULL... ya que nunca mandamos proveedor (desde el sp pasado es NULL siempre)

IF vCual IN (NULL, 'NO')
THEN
	LEAVE spVerCosto_label;
END IF;

IF vCual IN ('ESTANDAR', 'REPOSICION') AND vSubCuenta IS NOT NULL
THEN
	SELECT
	  CostoEstandar,
	  CostoReposicion
	INTO
	  vEstandar,
	  vReposicion
	FROM %%PREFIX%%intelisis_ArtSub
	WHERE Articulo = vArticulo
	AND SubCuenta = vSubCuenta;

	IF FOUND_ROWS() = 0 AND NOT EXISTS(SELECT * FROM ArtSub WHERE Articulo = vArticulo)
	THEN
		SELECT
		  CostoEstandar,
		  CostoReposicion
		INTO
		  vEstandar,
		  vReposicion
		FROM %%PREFIX%%intelisis_Art
		WHERE Articulo = vArticulo;
	END IF;
ELSE
	IF vCostoNivelOpcion  = 1 AND vSubCuenta IS NOT NULL
	THEN
		SELECT
			MonedaCosto, 
			UltimoCosto,
			UltimoCostoSinGastos,
			Art.CostoEstandar,
			Art.CostoReposicion,
			CostoPromedio,
			IFNULL(vPrecio, PrecioLista),
			Margen,
			Art.Impuesto1
		INTO
			vArtMoneda, 
		   vUltimo,
		   vUltimoSinGastos,
		   vEstandar,
		   vReposicion,
		   vPromedio,
		   vPrecio,
		   vMargen,
		   vImpuesto1
		FROM %%PREFIX%%intelisis_Art Art
		LEFT OUTER JOIN %%PREFIX%%intelisis_ArtSubCosto ArtSubCosto ON Art.Articulo = ArtSubCosto.Articulo AND ArtSubCosto.Sucursal = vSucursal AND ArtSubCosto.Empresa = vEmpresa AND ArtSubCosto.SubCuenta = vSubCuenta
		WHERE Art.Articulo = vArticulo;
	ELSE
		SELECT
			MonedaCosto, 
		   UltimoCosto,
		   UltimoCostoSinGastos,
		   Art.CostoEstandar,
		   Art.CostoReposicion,
		   CostoPromedio,
		   IFNULL(vPrecio, PrecioLista),
		   Margen,
		   IFNULL(Art.Impuesto1, 0),
		   IFNULL(Art.Impuesto2, 0),
		   IFNULL(Art.Impuesto3, 0)
		INTO
			vArtMoneda,
		   vUltimo,
		   vUltimoSinGastos,
		   vEstandar,
		   vReposicion,
		   vPromedio,
		   vPrecio,
		   vMargen,
		   vImpuesto1,
		   vImpuesto2,
		   vImpuesto3
		FROM %%PREFIX%%intelisis_Art Art
		LEFT OUTER JOIN %%PREFIX%%intelisis_ArtCosto ArtCosto ON Art.Articulo = ArtCosto.Articulo AND ArtCosto.Sucursal = vSucursal AND ArtCosto.Empresa = vEmpresa 
		WHERE Art.Articulo = vArticulo;
	END IF;
END IF;

IF vCfgImpInc = 1
THEN
  SET vJuntarImpuestos = ((100+vImpuesto2)*(1+((vImpuesto1)/100))-100);
  SET vPrecio = (vPrecio-vImpuesto3)/(1+(vJuntarImpuestos/100));
END IF;


IF vCual = 'PROMEDIO' THEN     SET vCosto = vPromedio;
ELSEIF vCual = 'ESTANDAR' THEN    SET vCosto = vEstandar;
ELSEIF vCual = 'REPOSICION' THEN  SET vCosto = vReposicion;
ELSEIF vCual = 'PRECIO LISTA' THEN SET vCosto = vPrecio;
ELSEIF vCual = 'MARGEN' THEN      SET vCosto = (vPrecio*(100-vMargen))/100;
ELSEIF vCual = 'ULTIMO (AUTOTRANS)' THEN      SET vCosto = vUltimo/(1+(vImpuesto1/100));
ELSEIF vCual = 'ULTIMO COSTO S/GASTO' THEN SET vCosto = vUltimoSinGastos;
ELSE SET vCosto = vUltimo; END IF;

-- Cambio esto en vez de spMoneda porque siempre la accion es NULL
-- En vez de depender de Mon, traer de tabla currencies
IF vMovMoneda<>vArtMoneda
THEN
	SELECT
		currencyexchangerate, 
	   currencydecimalplace
	INTO
		vTipoCambioBase,
		vToleranciaBase
	FROM %%PREFIX%%currencies
	WHERE currencyname = vMovMoneda;

	SET vMinimo = vTipoCambioBase * (1 - (vToleranciaBase/100.0)),
		vMaximo = vTipoCambioBase * (1 + (vToleranciaBase/100.0));
	IF vMovTipoCambio < vMinimo THEN SET vOk = 35080;
	ELSEIF vMovTipoCambio > vMaximo THEN SET vOk = 35090; END IF;
END IF;

SET vMovCosto = IFNULL(vCosto*vArtFactor, 0.0);
/* Quito esto porque no usamos xps
IF vCfgMultiUnidadesNivel = 'ARTICULO'
  EXEC xpArtUnidadFactor vArticulo, NULL, vMovUnidad, vUnidadFactor OUTPUT, vDecimales OUTPUT, vOk OUTPUT
ELSE
  EXEC xpUnidadFactor vArticulo, NULL, vMovUnidad, vUnidadFactor OUTPUT, vDecimales OUTPUT
	*/
SET vMovCosto = vMovCosto*vUnidadFactor;

SET vMovCosto = ROUND(vMovCosto, 2);
-- IF vConReturn = 1 THEN SELECT vMovCosto AS 'Costo'; END IF;

END//
DELIMITER ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;