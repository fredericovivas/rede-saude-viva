# Rede Saúde Viva

Protótipo académico (UC Análise e Modelação de Software) de um sistema web
para gestão de consultas e histórico clínico, em PHP + MySQL.

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