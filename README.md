<h1>Table Sharding</h1>

A Yii extension that horizontally partitions large tables for easy searching and faster query.

### Requirements

1. Yii 1.1.14 <b>(Not yet tested on other version)</b>
2. MySQL Table



<b>Table Schema</b>:
```bash
CREATE TABLE IF NOT EXISTS `shardtable` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `columnName` varchar(255) NOT NULL,
  `count` int(11) NOT NULL,
  `dateSharded` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
```


### Usage

* Create an extensions folder under protected if none exists.
* Add the php file in your import array

```bash
import=>array(
    ...
    'application.extensions.Sharding',
),
```
* Edit tableName method on your Models

```bash
public function tableName()
{
  /**
   * $tablename refers to the table you want to shard
   * $limit refers to the max row of your table
   */
  $var = new Sharding('$tablename',$limit);
  return $shard->useTable();
}
```
### Contributing

You can contribute to this project by:

1. Browse for issues, proposals, and report for bugs.
2. Fork my repo, make some changes and issue a pull request.

Whatever contribution is welcomed (may it be constructive criticism, feedbacks, violent reactions, or if you feel that my code is just plain stupid).

### Further Features

Features that I will be working on in the near future.

1. Asynchronous sharding
2. Scheduled sharding

### License

See LICENSE for details.

<h3>WARNING! USE AT YOUR OWN RISK </h3>
