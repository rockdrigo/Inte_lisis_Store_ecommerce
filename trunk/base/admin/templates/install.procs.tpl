/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;

-- Dumping structure for procedure store_db.%%PREFIX%%spSincronizacionPedidos
DELIMITER //
DROP PROCEDURE IF EXISTS `%%PREFIX%%spSincronizacionPedidos`//
CREATE DEFINER=`root`@`localhost` PROCEDURE `%%PREFIX%%spSincronizacionPedidos`(IN `ID` INT, IN `Estatus` INT)
BEGIN
DECLARE Vcustid, syncID int;
END//
DELIMITER ;

-- Dumping structure for trigger store_db.%%PREFIX%%sincronizacion_tgbi
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `%%PREFIX%%sincronizacion_tgbi` BEFORE INSERT ON `%%PREFIX%%sincronizacion` FOR EACH ROW BEGIN 
	IF (NEW.sincroUID IS NULL OR NEW.sincroUID = "") THEN
	SET NEW.sincroUID = UUID();
	END IF; 
END//
DELIMITER ;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;