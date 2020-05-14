/*
    test2_B1
    test2_N1
    test2_NAMES
    �������� ������ ���������� ������������� ������ � ��������� test2_
*/

-- ������� 1, ����� ������������ ������, ������ ��� ������ � ������
select max(a1) as a1, max(a2) as a2, max(a3) as a3 from (
    select count(1) as a1, null as a2, null as a3 from test2_N1
    union
    select null as a1,count(1) as a2, null as a3 from test2_B1 b1,test2_NAMES names 
        where b1.num_sc = names.num_sc                  -- ������ �� ������ ������ 
            and regexp_instr(names.name,'[�]') > 0      -- ����� ����� instr, �� ��������� �� �������� �������� �������
    union
    select null as a1, null as a2, count(1) as a3 
        from test2_B1 b1, test2_N1 n1,test2_NAMES names 
        where b1.num_sc = names.num_sc and regexp_instr(names.name,'[�]') > 0
            
            and n1.regn = 1481 and b1.regn = n1.regn 
            and b1.iitg > 0                              -- ��������� �������� ��� �� ���������� dbf � ���� ���� �������� � �����������
            --id ��������� 1481 select * from test2_n1 where upper(name_b) like '%����%'
);

-- ������� 2 ��� ��������� ��������, ������� ������ ����������� ��������, ������ ������� ������� �������� ����� �� ���� � ��������, ��� ������������� ������ ������ ��� ������� (� ������������ ����� ������ �� �����)
-- ����������� ����� �� ������ ������ - ���������� ��������, ������ ����� 30302 � 30301, ���������� �� ����
select n1.name_b bank, b1.num_sc num_acc, b1.iitg summ 
    from test2_n1 n1, test2_B1 b1
    left join test2_B1 b2 on b2.regn = b1.regn and b2.iitg > b1.iitg and b2.num_sc <> 'ITGAP' and instr(b2.num_sc,9) = 0 
    where b2.iitg is null and n1.regn = b1.regn and b1.num_sc <> 'ITGAP' and instr(b1.num_sc,9) = 0;
    
-- ������� 3 ��� ��������� ��������, ���������� � �����(��) ������� � �������� ��� ����� � ����������� ���������, � � ������� ����������� ���������� ��������
-- ������ � ��� � �� ����� ���� �������� ��� 100 ������ � ��� ��� ��� ��������� ������? 
select sum(b1.iitg) summ from test2_n1 n1
    left join test2_b1 b1 on b1.regn = n1.regn and b1.num_sc between '42302' and '42307'
    group by n1.name_b having sum(b1.iitg) <= 10000000000;
    
-- ������� 4, ����� ��� ��������, ������ ���� �� ���� 2020
define value1 = to_date('01.01.19')
define value2 = to_date('01.01.20')

select sid, max(sber), max(alfa), max(spb) from (
    --���� 1481
    select '������� ���' sid, sum(b1.iitg) sber, null alfa, null spb from test2_b1 b1 where b1.regn = 1481 and b1.DT between &&value1 and ADD_MONTHS(&&value1,12)
    union
    select '������� ���', sum(b1.iitg), null, null from test2_b1 b1 where b1.regn = 1481 and b1.DT between &&value2 and ADD_MONTHS(&&value2,12)
    union
    --����� 1326
    select '������� ���', null, sum(b1.iitg), null from test2_b1 b1 where b1.regn = 1326 and b1.DT between &&value1 and ADD_MONTHS(&&value1,12)
    union
    select '������� ���', null, sum(b1.iitg), null from test2_b1 b1 where b1.regn = 1326 and b1.DT between &&value2 and ADD_MONTHS(&&value2,12)
    union
    --�����-������ 436
    select '������� ���', null, null, sum(b1.iitg) from test2_b1 b1 where b1.regn = 436 and b1.DT between &&value1 and ADD_MONTHS(&&value1,12)
    union
    select '������� ���', null, null, sum(b1.iitg) from test2_b1 b1 where b1.regn = 436 and b1.DT between &&value2 and ADD_MONTHS(&&value2,12)
) group by sid;