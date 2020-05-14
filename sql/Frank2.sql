/*
    test2_B1
    test2_N1
    test2_NAMES
    название таблиц аналогичны наименованием файлов с префиксом test2_
*/

-- задание 1, через агрегирующий запрос, первое что пришло в голову
select max(a1) as a1, max(a2) as a2, max(a3) as a3 from (
    select count(1) as a1, null as a2, null as a3 from test2_N1
    union
    select null as a1,count(1) as a2, null as a3 from test2_B1 b1,test2_NAMES names 
        where b1.num_sc = names.num_sc                  -- похоже на нужную св€зку 
            and regexp_instr(names.name,'[ы]') > 0      -- можно через instr, но регул€рки на практике работают быстрее
    union
    select null as a1, null as a2, count(1) as a3 
        from test2_B1 b1, test2_N1 n1,test2_NAMES names 
        where b1.num_sc = names.num_sc and regexp_instr(names.name,'[ы]') > 0
            
            and n1.regn = 1481 and b1.regn = n1.regn 
            and b1.iitg > 0                              -- напомните спросить чем вы разбераете dbf у мен€ была проблема с кодировками
            --id сбербанка 1481 select * from test2_n1 where upper(name_b) like '%—Ѕ≈–%'
);

-- задание 2 без вложенных запросов, хороший пример оптимизации запросов, делаем зав€зку таблицы кредитов самой на себе с условием, что прив€зываемые данные больше чем текущие (у максимальных таких данных не будет)
-- наблюдаютс€ дубли по парным счетам - начислени€ списани€, пример счета 30302 и 30301, выкидывать не стал
select n1.name_b bank, b1.num_sc num_acc, b1.iitg summ 
    from test2_n1 n1, test2_B1 b1
    left join test2_B1 b2 on b2.regn = b1.regn and b2.iitg > b1.iitg and b2.num_sc <> 'ITGAP' and instr(b2.num_sc,9) = 0 
    where b2.iitg is null and n1.regn = b1.regn and b1.num_sc <> 'ITGAP' and instr(b1.num_sc,9) = 0;
    
-- задание 3 без вложенных запросов, прицепл€ем к банку(ам) платежи с условием что счета в необходимом диапазоне, а в условие группировки откидываем —бербанк
-- кстати € так и не пон€л куда вылетели ещЄ 100 банков в них что нет депозитов вообще? 
select sum(b1.iitg) summ from test2_n1 n1
    left join test2_b1 b1 on b1.regn = n1.regn and b1.num_sc between '42302' and '42307'
    group by n1.name_b having sum(b1.iitg) <= 10000000000;
    
-- задание 4, вывод был неполный, грузил файл за март 2020
define value1 = to_date('01.01.19')
define value2 = to_date('01.01.20')

select sid, max(sber), max(alfa), max(spb) from (
    --сбер 1481
    select 'ѕрошлый год' sid, sum(b1.iitg) sber, null alfa, null spb from test2_b1 b1 where b1.regn = 1481 and b1.DT between &&value1 and ADD_MONTHS(&&value1,12)
    union
    select '“екущий год', sum(b1.iitg), null, null from test2_b1 b1 where b1.regn = 1481 and b1.DT between &&value2 and ADD_MONTHS(&&value2,12)
    union
    --альфа 1326
    select 'ѕрошлый год', null, sum(b1.iitg), null from test2_b1 b1 where b1.regn = 1326 and b1.DT between &&value1 and ADD_MONTHS(&&value1,12)
    union
    select '“екущий год', null, sum(b1.iitg), null from test2_b1 b1 where b1.regn = 1326 and b1.DT between &&value2 and ADD_MONTHS(&&value2,12)
    union
    --санкт-петерб 436
    select 'ѕрошлый год', null, null, sum(b1.iitg) from test2_b1 b1 where b1.regn = 436 and b1.DT between &&value1 and ADD_MONTHS(&&value1,12)
    union
    select '“екущий год', null, null, sum(b1.iitg) from test2_b1 b1 where b1.regn = 436 and b1.DT between &&value2 and ADD_MONTHS(&&value2,12)
) group by sid;