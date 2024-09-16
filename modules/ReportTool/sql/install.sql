
DROP FUNCTION IF EXISTS FormatDate;
#SpecialSql

CREATE FUNCTION FormatDate (p_date DATE) RETURNS VARCHAR(255) READS SQL DATA
BEGIN
    DECLARE return_date VARCHAR(255);
    DECLARE date_format TINYINT(1);

    SELECT gl_value
    INTO date_format
    FROM globals WHERE gl_name = 'date_display_format';

    SELECT
        CASE
        WHEN date_format = 0 THEN CAST(p_date AS CHAR)
        WHEN date_format = 1 THEN DATE_FORMAT(p_date, '%m/%d/%Y')
        WHEN date_format = 2 THEN DATE_FORMAT(p_date, '%d/%m/%Y')
        END
    INTO return_date;

    RETURN return_date;


END;
#EndSpecialSql


DROP FUNCTION IF EXISTS `GetHebTitle`;
#SpecialSql

CREATE FUNCTION GetHebTitle(p_title varchar(255)) RETURNS varchar(255) READS SQL DATA
BEGIN

   DECLARE v_title varchar(255);

    select d.definition
    INTO v_title
	from lang_constants c
	join lang_definitions d on d.cons_id = c.cons_id and lang_id= (select lang_id from lang_languages where lang_code = 'he')
	where c.constant_name = p_title;

   RETURN ifnull(v_title, p_title);

END;

#EndSpecialSql
