-- Copyright (C) 2022-2023 EVARISK <technique@evarisk.com>
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see https://www.gnu.org/licenses/.

-- 1.1.0

INSERT INTO `llx_c_shortener_url_type` (`rowid`, `entity`, `ref`, `label`, `description`, `active`, `position`) VALUES(1, 0, 'Payment', 'Payment', '', 1, 1);
INSERT INTO `llx_c_shortener_url_type` (`rowid`, `entity`, `ref`, `label`, `description`, `active`, `position`) VALUES(2, 0, 'Signature', 'Signature', '', 1, 10);
INSERT INTO `llx_c_shortener_url_type` (`rowid`, `entity`, `ref`, `label`, `description`, `active`, `position`) VALUES(3, 0, 'Document', 'Document', '', 1, 20);
INSERT INTO `llx_c_shortener_url_type` (`rowid`, `entity`, `ref`, `label`, `description`, `active`, `position`) VALUES(4, 0, 'Other', 'Other', '', 1, 30);
