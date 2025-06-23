#!/bin/bash

# Colores para que se vea bonito
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${BLUE}🚀 Iniciando despliegue de E-commerce Fabrizzio...${NC}"

# Detener contenedores anteriores si existen
echo -e "${BLUE}🛑 Deteniendo contenedores anteriores...${NC}"
docker-compose down

# Limpiar imágenes no utilizadas
echo -e "${BLUE}🧹 Limpiando imágenes no utilizadas...${NC}"
docker image prune -f

# Levantar todos los servicios
echo -e "${BLUE}🔧 Levantando servicios...${NC}"
docker-compose up -d

# Esperar a que los servicios estén listos
echo -e "${BLUE}⏳ Esperando a que los servicios estén listos...${NC}"
sleep 45

# Verificar que los servicios estén funcionando
echo -e "${BLUE}✅ Verificando servicios...${NC}"

# Test WordPress
if curl -s http://localhost:8080 > /dev/null; then
    echo -e "${GREEN}✅ WordPress funcionando en http://localhost:8080${NC}"
else
    echo -e "${RED}❌ WordPress no responde${NC}"
fi

# Test Laravel
if curl -s http://localhost:8000/api/health > /dev/null; then
    echo -e "${GREEN}✅ Laravel API funcionando en http://localhost:8000${NC}"
else
    echo -e "${RED}❌ Laravel API no responde${NC}"
fi

# Test PHPMyAdmin
if curl -s http://localhost:8081 > /dev/null; then
    echo -e "${GREEN}✅ PHPMyAdmin disponible en http://localhost:8081${NC}"
else
    echo -e "${RED}❌ PHPMyAdmin no responde${NC}"
fi

# Mostrar estado de contenedores
echo -e "\n${BLUE}📊 Estado de contenedores:${NC}"
docker-compose ps

echo -e "\n${GREEN}🎉 ¡Despliegue completado!${NC}"
echo -e "\n📋 URLs disponibles:"
echo -e "🌐 Frontend WordPress: http://localhost:8080"
echo -e "🔧 Backend Laravel: http://localhost:8000"
echo -e "📊 API Health: http://localhost:8000/api/health"
echo -e "🗄️ PHPMyAdmin: http://localhost:8081"
echo -e "\n🔑 Credenciales BD: root / root_password"