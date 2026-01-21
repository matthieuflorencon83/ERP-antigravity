
from fastmcp import FastMCP
import os

# Define the allowed root directory
ROOT_DIR = os.path.abspath("c:\\Dev")

# Initialize FastMCP
mcp = FastMCP("Antigravity Filesystem")

def validate_path(path: str) -> str:
    """Ensure path is absolute and within ROOT_DIR."""
    abs_path = os.path.abspath(os.path.join(ROOT_DIR, path))
    if not abs_path.startswith(ROOT_DIR):
        raise ValueError(f"Access denied: Path must be within {ROOT_DIR}")
    return abs_path

@mcp.tool()
def read_file(path: str) -> str:
    """Read the contents of a file."""
    safe_path = validate_path(path)
    if not os.path.exists(safe_path):
        return f"Error: File not found at {safe_path}"
    try:
        with open(safe_path, 'r', encoding='utf-8') as f:
            return f.read()
    except Exception as e:
        return f"Error reading file: {str(e)}"

@mcp.tool()
def write_file(path: str, content: str) -> str:
    """Write content to a file (overwrites existing)."""
    safe_path = validate_path(path)
    try:
        os.makedirs(os.path.dirname(safe_path), exist_ok=True)
        with open(safe_path, 'w', encoding='utf-8') as f:
            f.write(content)
        return f"Successfully wrote to {safe_path}"
    except Exception as e:
        return f"Error writing file: {str(e)}"

@mcp.tool()
def list_directory(path: str = ".") -> str:
    """List contents of a directory."""
    safe_path = validate_path(path)
    if not os.path.isdir(safe_path):
        return f"Error: Not a directory at {safe_path}"
    try:
        items = os.listdir(safe_path)
        return "\n".join(items)
    except Exception as e:
        return f"Error listing directory: {str(e)}"

if __name__ == "__main__":
    mcp.run()
