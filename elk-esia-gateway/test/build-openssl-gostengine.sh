cd /tmp
# Полностью очищаем предыдущие остаточные папки сборщика
rm -rf engine

# Клонируем актуальный корневой репозиторий вместе с субмодулями
git clone --recurse-submodules https://github.com/gost-engine/engine.git
cd engine

# Переключаем указатель на стабильный последний релиз
git checkout v3.0.3
git submodule update --init --recursive

# Выполняем нативную компиляцию
mkdir build && cd build
cmake -DCMAKE_BUILD_TYPE=Release -DCMAKE_C_FLAGS="-Wno-error -Wno-stringop-overflow" ..
make
sudo make install

# Перезапускаем встроенную диагностику OpenSSL 3 для верификации статуса
openssl engine gost

