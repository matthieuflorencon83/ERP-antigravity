
from fastmcp import FastMCP
import psycopg2
from psycopg2.extras import RealDictCursor

# Configuration
DB_CONFIG = {
    "dbname": "antigravity_db",
    "user": "postgres",
    "password": "loan221213",
    "host": "localhost",
    "port": "5432"
}

mcp = FastMCP("Antigravity Postgres")

def get_connection():
    return psycopg2.connect(**DB_CONFIG)

@mcp.tool()
def list_tables() -> str:
    """List all public tables in the database."""
    query = """
        SELECT table_name 
        FROM information_schema.tables 
        WHERE table_schema = 'public'
        ORDER BY table_name;
    """
    try:
        with get_connection() as conn:
            with conn.cursor() as cur:
                cur.execute(query)
                tables = [row[0] for row in cur.fetchall()]
                return "\n".join(tables) if tables else "No tables found."
    except Exception as e:
        return f"Error: {e}"

@mcp.tool()
def describe_table(table_name: str) -> str:
    """Get the schema definition for a specific table."""
    query = """
        SELECT column_name, data_type, is_nullable
        FROM information_schema.columns
        WHERE table_name = %s
        ORDER BY ordinal_position;
    """
    try:
        with get_connection() as conn:
            with conn.cursor() as cur:
                cur.execute(query, (table_name,))
                rows = cur.fetchall()
                if not rows:
                    return f"Table '{table_name}' not found."
                
                output = [f"Schema for {table_name}:"]
                for col, dtype, nullable in rows:
                    null_str = "NULL" if nullable == 'YES' else "NOT NULL"
                    output.append(f"- {col} ({dtype}, {null_str})")
                return "\n".join(output)
    except Exception as e:
        return f"Error: {e}"

@mcp.tool()
def run_read_query(query: str) -> str:
    """Run a read-only SQL query (SELECT only)."""
    if not query.strip().upper().startswith("SELECT"):
        return "Error: Only SELECT queries are allowed."
    
    try:
        with get_connection() as conn:
            with conn.cursor(cursor_factory=RealDictCursor) as cur:
                cur.execute(query)
                rows = cur.fetchall()
                if not rows:
                    return "No results."
                
                # Format as simple text representation of list of dicts
                return str(rows)
    except Exception as e:
        return f"Error executing query: {e}"

if __name__ == "__main__":
    mcp.run()
