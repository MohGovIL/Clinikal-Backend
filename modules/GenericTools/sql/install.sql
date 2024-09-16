INSERT INTO `globals` (`gl_name`, `gl_value`) VALUES ('s3_version', '2006-03-01');


DROP FUNCTION IF EXISTS `GetOptionTitle`;
#SpecialSql

CREATE FUNCTION GetOptionTitle(p_list_id varchar(255), p_option_id varchar(100)) RETURNS varchar(255) READS SQL DATA
BEGIN

   DECLARE v_title varchar(255);

select lo.title
INTO v_title
from `list_options` lo
where lo.list_id = p_list_id and
        lo.option_id = p_option_id;

RETURN v_title;

END;
#EndSpecialSql



DROP FUNCTION IF EXISTS `translateString`;
#SpecialSql

CREATE FUNCTION translateString(p_title varchar(255), p_lang varchar(5)) RETURNS varchar(255) READS SQL DATA
BEGIN

   DECLARE v_title varchar(255);

select d.definition
INTO v_title
from lang_constants c
         join lang_definitions d on d.cons_id = c.cons_id and lang_id= (select lang_id from lang_languages where lang_code = p_lang)
where c.constant_name = p_title;

RETURN ifnull(v_title, p_title);

END;
#EndSpecialSql


