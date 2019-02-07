#!/bin/bash
#####
# 奇数に重いタスクを発生させる
#

BASE_DIR=$(cd $(dirname $0);pwd)
cd $BASE_DIR

for i in {1..10}
do
  if [ `expr $i % 2` == 0 ]; then
    php newTask.php ".....Task${i} (5秒)"
  else
    php newTask.php ".......Task${i} (7秒)"
  fi
done
