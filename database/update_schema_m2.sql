-- 11. Module 2 : Besoin
CREATE TABLE IF NOT EXISTS besoin_ligne (
    id INT AUTO_INCREMENT PRIMARY KEY,
    groupe_calcul VARCHAR(255), -- Ex: "7016_ALU"
    date_calcul TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    config_calcul JSON -- Le résultat du calepinage Python
) ENGINE=InnoDB;
