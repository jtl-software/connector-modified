3.11.0
------
- CO-1344 - Refactored installer classes
- CO-1553 - Fixed problem with image deletion
- CO-1936 - Fixed customer order import query 

3.10.0
------
- CO-1863 - Fixed problem with createImageFilePath and other minor bugs
- Switched to connector components xtc library

3.9.0
-----
- CO-1779 - Fixed multilingual var combi item not selectable in the shop

3.8.0
-----
- CO-1464 - Added product tax class guessing on product push
- CO-1015 - Added extended error info messages
- CO-1567 - Fixed invalid variation product id in customer order
- CO-1788 - Fixed modified version 2.0.5.0 compatibility

3.7.1
-----
- Fixed update procedure
- Fixed product endpoint creation on pull
- Fixed category push

3.7.0
-----
- CO-987  Switched to importing payments only for already imported orders
- CO-1083 Added support for sorting multi dimensional simple product variants
- CO-1185 Fixed problem with duplicated product variants after renaming

3.6.0
-----
- CO-1422 Fixed endpoint column type in checksum table
- CO-1453 Added variation names to customer order item during pull
- CO-1502 Added not overriding features.json on every updated
- CO-1503 Fixed product image push

3.5.0
-----
- CO-1369 Fixed country code in customer order
- CO-1392 Added support for grad discount (ot_grad_discount) module
- Fixed and improved installation/config procedure

3.4.0
-----
- CO-1153 Added importing customers bank details in customer orders paid by direct debit
- CO-1280 Added has customer account flag to customer import
- CO-1300 Removed use_varcombi_logic flag. Connector can differentiate by its own if it is a simple or complex product variant
- CO-1307 Fixed customer order statistics

3.3.0
-----
- CO-1212 Fixed using variation names for variations
- CO-1299 Fixed setting default shipping status as fallback
- Added importing customer admin group into  JTL-Wawi

3.2.0
-----
- CO-1214 - Fixed installation/config procedure 

3.1.0
-----
- CO-1184 - Fixed import discount items
- CO-1127 - Fixed problems with DateTime properties

3.0.0
-----
- CO-1027 Fixed importing cod fee
- CO-308 VarCombi support
- CO-419 Fixed importing order language

2.0.3
-----
- CO-917  Fixed importing coupons in orders
- CO-1027 Fixed importing cash on delivery fee in orders
- CO-1051 Fixed sending base price on products

2.0.2.4
-----
 - CO-977 Fixed missing default column values when saving manufacturer and category
 
2.0.2.3
-----
 - CO-749 Fixed manufacturer push
 
2.0.2.2
 -----
 - Updated primary key mapper

2.0.2.1
-----
 - CO-634 Disable unit pulls temporarily

2.0.2
-----
- [217f3276] [CO-295]
  php version fix
  
- [7ca718aa] [CO-321]
  'ot_coupon' and 'ot_discount' handling
  
- [8e7befd2] [CO-323]
  build process fix
  
- [f1ed687f] [CO-295]
  customer address fix
  
- [58a0df6a] [CO-315]
  order price fix

2.0.1
-----
- [d8225b30]
  vpe fix

1.5
------
- [8cd1558]
  workaround for shipping name

- [59b4bb0]
  added delivery tax rate

- [bbc2163]
  fixed mapping table creation
  fixed payment method name
  fixed order item price calculation

- [1e3b8f6]
  add link table index checks

- [f7b43db]
  image bugfix

- [3fee431]
  fixed image pull sort

- [e6e26da]
  added product templates

- [bf909de]
  fixed additional costs/discounts for orders

- [db4ac83]
  fixed iso mappings

- [b25773e]
  added crossselling id

- [a4bbee4]
  added utf8 encode option for hacked shops/themes

- [196a19d]
  updated changelog

- [be37823]
  updated version

1.4
------
- [4b7f6fd]
  updated tokenloader
  fixed invalid queries

1.3
------
- [1ed639f]
  added variation value ean
  fixed encoding
  raised connector version

1.2
------
- [da50cdd]
  fixed some propery encodings
  avoid errors with empty shipping modules
  fixed method definitions

- [af71e41]
  added changelog

1.1
------
- [fedd103]
  cleaned up uses

- [c4ae21b]
  removed setnames

1.0.13
------
- [6c06029]
  fixed exception handler
  added customer vat
  removed setnames for db connection

