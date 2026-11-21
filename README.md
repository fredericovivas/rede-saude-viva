# Rede Saúde Viva

Protótipo académico (UC Análise e Modelação de Software) de um sistema web
para gestão de consultas e histórico clínico, em PHP + MySQL.

## Porquê este projeto

Este projeto nasceu na unidade curricular de Análise e Modelação de Software. O objetivo da UC **não era a tecnologia em si**, mas aplicar o ciclo completo de engenharia de software: levantar requisitos, modelá-los em UML e gerir o desenvolvimento com Scrum.

O que o trabalho tinha de demonstrar, e que ficou documentado no relatório entregue:

- **Requisitos:** 13 funcionais e 7 não funcionais
- **User stories** priorizadas com MoSCoW e estimadas com a sequência de Fibonacci (12 no backlog, 11 entregues)
- **Modelação UML:** casos de uso, classes, 3 diagramas de sequência e 2 de atividades
- **Scrum em 3 sprints** (8, 21 e 32 story points), com planning, review e retrospetiva
- **Protótipo funcional** do MVP definido: gestão de consultas e histórico clínico

As tecnologias (PHP, MySQL, HTML/CSS e XAMPP) foram escolhidas por mim por serem simples de montar e de correr localmente, o que permitiu concentrar o esforço na análise, na modelação e no processo, e não na infraestrutura. Por isso o protótipo é propositadamente simples e não é um produto para produção (ver o aviso abaixo).

## Aviso
Projeto de estudo com dados fictícios. NÃO usar em produção: as contas demo
têm password conhecida, não há HTTPS e não existe proteção CSRF.

## Requisitos
XAMPP (PHP 8+ e MariaDB/MySQL).

## Instalação
1. Copiar a pasta prototipo_saude_viva para htdocs e chamar-lhe "saude_viva"
   (os links da aplicação usam o caminho /saude_viva/).
2. No phpMyAdmin, importar BaseDados/schema.sql e depois BaseDados/seed_demo.sql.
3. Copiar includes/db.example.php para includes/db.php e ajustar a porta se necessário.
4. Abrir http://localhost/saude_viva/

## Contas de demonstração (password: teste123)
- admin@saudeviva.pt (admin)
- joao.silva@saudeviva.pt e ana.costa@saudeviva.pt (médicos)
- maria@saudeviva.pt (utente)
- carla@saudeviva.pt (rececionista)

## Licença
MIT. Ver o ficheiro LICENSE.
