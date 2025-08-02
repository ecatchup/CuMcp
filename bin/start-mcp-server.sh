#!/bin/bash

# baserCMS MCP Server Start Script

# スクリプトのディレクトリを取得
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../../../.." && pwd)"

# baserCMSのルートディレクトリに移動
cd "$PROJECT_ROOT"

# デフォルト値
TRANSPORT="stdio"
HOST="localhost"
PORT="3000"
CONFIG_FILE=""

# 使用方法表示
show_usage() {
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Options:"
    echo "  -t, --transport TRANSPORT  Transport type (stdio, sse) [default: stdio]"
    echo "  -h, --host HOST            Host for SSE mode [default: localhost]"
    echo "  -p, --port PORT            Port for SSE mode [default: 3000]"
    echo "  -c, --config CONFIG        Configuration file path"
    echo "  --help                     Show this help message"
    echo ""
    echo "Examples:"
    echo "  $0                                    # Start with stdio transport"
    echo "  $0 -t sse -h 0.0.0.0 -p 8080        # Start with SSE transport"
    echo "  $0 -c /path/to/config.php            # Use custom config file"
}

# コマンドライン引数の解析
while [[ $# -gt 0 ]]; do
    case $1 in
        -t|--transport)
            TRANSPORT="$2"
            shift 2
            ;;
        -h|--host)
            HOST="$2"
            shift 2
            ;;
        -p|--port)
            PORT="$2"
            shift 2
            ;;
        -c|--config)
            CONFIG_FILE="$2"
            shift 2
            ;;
        --help)
            show_usage
            exit 0
            ;;
        *)
            echo "Unknown option: $1"
            show_usage
            exit 1
            ;;
    esac
done

# baserCMSのcakeコマンドが存在するかチェック
if [ ! -f "$PROJECT_ROOT/bin/cake" ]; then
    echo "Error: baserCMS cake command not found at $PROJECT_ROOT/bin/cake"
    echo "Please run this script from the baserCMS project root or check the installation."
    exit 1
fi

# MCPサーバーの起動
echo "Starting baserCMS MCP Server..."
echo "Project Root: $PROJECT_ROOT"
echo "Transport: $TRANSPORT"

if [ "$TRANSPORT" = "sse" ]; then
    echo "Host: $HOST"
    echo "Port: $PORT"
fi

if [ -n "$CONFIG_FILE" ]; then
    echo "Config File: $CONFIG_FILE"
fi

echo ""

# コマンド構築
CAKE_COMMAND="bin/cake cu_mcp.server --transport=$TRANSPORT"

if [ "$TRANSPORT" = "sse" ]; then
    CAKE_COMMAND="$CAKE_COMMAND --host=$HOST --port=$PORT"
fi

if [ -n "$CONFIG_FILE" ]; then
    CAKE_COMMAND="$CAKE_COMMAND --config=$CONFIG_FILE"
fi

# MCPサーバー実行
exec $CAKE_COMMAND
