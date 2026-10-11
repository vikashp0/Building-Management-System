import os
import requests
import streamlit as st
import pandas as pd

from dotenv import load_dotenv
from pyspark.sql import SparkSession


# =========================================================
# CONFIGURATION
# =========================================================

load_dotenv()

# Streamlit Cloud -> st.secrets
# Local PC -> .env
try:
    API_KEY = st.secrets.get("RAILRADAR_API_KEY")
except Exception:
    API_KEY = None

if not API_KEY:
    API_KEY = os.getenv("RAILRADAR_API_KEY")

API_URL = "https://api.railradar.in/v1/trains/{number}/live"


# =========================================================
# PAGE CONFIG
# =========================================================

st.set_page_config(
    page_title="Railway Live Analytics",
    page_icon="🚆",
    layout="wide",
    initial_sidebar_state="collapsed"
)


# =========================================================
# CSS
# =========================================================

st.markdown(
    """
    <style>
    .stApp {
        background-color: #080b12;
    }

    .block-container {
        max-width: 1400px;
        padding-top: 2rem;
        padding-bottom: 3rem;
    }

    .app-header {
        background: linear-gradient(135deg, #111827, #172554);
        border: 1px solid #263248;
        border-radius: 20px;
        padding: 28px;
        margin-bottom: 25px;
    }

    .search-box {
        background: #111827;
        border: 1px solid #263248;
        border-radius: 18px;
        padding: 22px;
        margin-bottom: 25px;
    }

    .footer-text {
        text-align: center;
        color: #64748b;
        padding-top: 30px;
        margin-top: 40px;
        border-top: 1px solid #1e293b;
    }

    div[data-testid="stMetric"] {
        background: #111827;
        border: 1px solid #263248;
        border-radius: 14px;
        padding: 12px;
    }
    </style>
    """,
    unsafe_allow_html=True
)


# =========================================================
# HEADER
# =========================================================

st.title("🚆 Railway Live Analytics")
st.caption(
    "Real-Time Indian Railway Monitoring & Big Data Analytics "
    "using RailRadar API + PySpark"
)

st.divider()


# =========================================================
# API KEY CHECK
# =========================================================

if not API_KEY:
    st.error("❌ RailRadar API key nahi mili.")
    st.info(
        "Streamlit Cloud me Settings → Secrets me "
        "RAILRADAR_API_KEY add karo."
    )
    st.code(
        'RAILRADAR_API_KEY = "YOUR_API_KEY"',
        language="toml"
    )
    st.stop()


# =========================================================
# API FUNCTION
# =========================================================

def get_train_data(train_number):
    url = API_URL.format(number=train_number)

    headers = {
        "Authorization": f"Bearer {API_KEY}",
        "Accept": "application/json"
    }

    try:
        response = requests.get(
            url,
            headers=headers,
            timeout=20
        )

        if response.status_code == 200:
            return True, response.json()

        if response.status_code == 401:
            return False, "API key invalid or expired."

        if response.status_code == 403:
            return False, "API access denied. Check RailRadar plan/key."

        if response.status_code == 404:
            return False, "Train data not found."

        if response.status_code == 429:
            return False, "API request limit exceeded. Try again later."

        if response.status_code >= 500:
            return False, "RailRadar server is temporarily unavailable."

        return False, f"API Error {response.status_code}"

    except requests.exceptions.Timeout:
        return False, "Request timeout ho gaya."

    except requests.exceptions.ConnectionError:
        return False, "Internet connection problem."

    except requests.exceptions.RequestException as e:
        return False, f"Network error: {e}"

    except Exception as e:
        return False, str(e)


# =========================================================
# SAFE VALUE FUNCTION
# =========================================================

def get_value(data, *keys):
    if not isinstance(data, dict):
        return None

    for key in keys:
        value = data.get(key)

        if value is not None and value != "":
            return value

    return None


# =========================================================
# FLATTEN NESTED DATA
# =========================================================

def flatten_dict(data, parent_key=""):
    """
    Converts one/multiple levels of nested dictionaries
    into simple Parameter/Value rows for Streamlit display.
    """
    rows = []

    if not isinstance(data, dict):
        return rows

    for key, value in data.items():
        full_key = f"{parent_key}.{key}" if parent_key else str(key)

        if isinstance(value, dict):
            rows.extend(flatten_dict(value, full_key))

        elif isinstance(value, list):
            rows.append({
                "Parameter": full_key,
                "Value": str(value)
            })

        else:
            rows.append({
                "Parameter": full_key,
                "Value": value
            })

    return rows


# =========================================================
# SEARCH SECTION
# =========================================================

st.subheader("🔎 Search Live Train")

col1, col2, col3 = st.columns(
    [4, 1, 1],
    vertical_alignment="bottom"
)

with col1:
    train_number = st.text_input(
        "Train Number",
        placeholder="Example: 22661",
        max_chars=6
    )

with col2:
    search_button = st.button(
        "🚆 Search",
        type="primary",
        use_container_width=True
    )

with col3:
    refresh_button = st.button(
        "🔄 Refresh",
        use_container_width=True
    )


# =========================================================
# EMPTY STATE
# =========================================================

if not search_button and not refresh_button:

    st.info(
        "Enter a train number and click Search "
        "to fetch real-time railway data."
    )

    st.markdown("### 📌 Project Features")

    f1, f2, f3, f4 = st.columns(4)

    with f1:
        st.metric("🚆 Live Tracking", "Real-Time")

    with f2:
        st.metric("⚡ Processing", "PySpark")

    with f3:
        st.metric("📡 Data Source", "RailRadar")

    with f4:
        st.metric("📊 Dashboard", "Streamlit")

    st.stop()


# =========================================================
# INPUT VALIDATION
# =========================================================

if not train_number:
    st.warning("⚠️ Please enter a train number.")
    st.stop()

train_number = train_number.strip()

if not train_number.isdigit():
    st.warning("⚠️ Train number must contain only digits.")
    st.stop()


# =========================================================
# API REQUEST
# =========================================================

with st.spinner("🔄 Fetching real-time railway data..."):
    success, result = get_train_data(train_number)


# =========================================================
# API ERROR
# =========================================================

if not success:
    st.error(f"❌ {result}")
    st.stop()


# =========================================================
# EXTRACT MAIN DATA
# =========================================================

data = result.get("data", {})

if not isinstance(data, dict):
    st.error("Unexpected API response received.")
    st.stop()


# =========================================================
# TRAIN DATA
# =========================================================

number = (
    get_value(data, "trainNumber")
    or train_number
)

name = (
    get_value(data, "trainName")
    or "Unknown Train"
)

start_date = (
    get_value(data, "startDate")
    or "N/A"
)

status = (
    get_value(data, "status")
    or "Unknown"
)

updated = (
    get_value(data, "lastUpdatedAt")
    or "N/A"
)


# =========================================================
# TRAIN INFORMATION
# =========================================================

train_info = data.get("train", {})

if not isinstance(train_info, dict):
    train_info = {}

train_type = (
    get_value(train_info, "type")
    or "N/A"
)

category = (
    get_value(train_info, "category")
    or "N/A"
)


# =========================================================
# SUCCESS
# =========================================================

st.success("✅ Live railway data received successfully.")


# =========================================================
# TRAIN HEADER
# =========================================================

st.markdown(f"## 🚆 {name}")

st.caption(
    f"Train Number: {number}  •  "
    f"{train_type}  •  {category}"
)


# =========================================================
# STATUS
# =========================================================

status_lower = str(status).lower()

if status_lower in ["running", "started", "active"]:

    st.success("🟢 TRAIN IS CURRENTLY RUNNING")

elif status_lower in ["not-started", "not_started"]:

    st.warning("🟡 TRAIN HAS NOT STARTED YET")

elif status_lower in ["cancelled", "canceled"]:

    st.error("🔴 TRAIN IS CANCELLED")

else:

    st.info(
        f"ℹ️ TRAIN STATUS: {str(status).upper()}"
    )


# =========================================================
# MAIN KPI
# =========================================================

st.subheader("📊 Live Overview")

c1, c2, c3, c4 = st.columns(4)

with c1:
    st.metric(
        "Train Number",
        number
    )

with c2:
    st.metric(
        "Train Type",
        train_type
    )

with c3:
    st.metric(
        "Category",
        category
    )

with c4:
    st.metric(
        "Status",
        str(status).upper()
    )


# =========================================================
# JOURNEY INFORMATION
# =========================================================

st.subheader("🛤️ Journey Information")

j1, j2, j3 = st.columns(3)

with j1:
    st.info(
        f"📅 **Journey Date**\n\n{start_date}"
    )

with j2:
    st.info(
        f"🔄 **Last Updated**\n\n{updated}"
    )

with j3:
    st.info(
        "📡 **Data Source**\n\n"
        "RailRadar Live API"
    )


# =========================================================
# TRAIN DETAILS TABLE
# =========================================================

st.subheader("🚉 Train Details")

details = pd.DataFrame(
    {
        "Parameter": [
            "Train Number",
            "Train Name",
            "Train Type",
            "Category",
            "Journey Date",
            "Current Status",
            "Last Updated"
        ],
        "Value": [
            number,
            name,
            train_type,
            category,
            start_date,
            status,
            updated
        ]
    }
)

st.dataframe(
    details,
    use_container_width=True,
    hide_index=True
)


# =========================================================
# CURRENT LOCATION & TELEMETRY
# =========================================================

current_location = data.get("currentLocation")

if current_location:

    st.subheader("📍 Current Location & Telemetry")

    if isinstance(current_location, dict):

        location_rows = flatten_dict(current_location)

        if location_rows:

            location_df = pd.DataFrame(location_rows)

            st.dataframe(
                location_df,
                use_container_width=True,
                hide_index=True
            )

            # Common telemetry values
            speed = get_value(
                current_location,
                "speedKmh",
                "speed",
                "speedKmH"
            )

            delay = get_value(
                current_location,
                "delayMinutes",
                "delay",
                "delayMins"
            )

            progress = get_value(
                current_location,
                "segmentProgress",
                "progress"
            )

            t1, t2, t3 = st.columns(3)

            with t1:
                st.metric(
                    "🚄 Speed",
                    f"{speed} km/h" if speed is not None else "N/A"
                )

            with t2:
                st.metric(
                    "⏱️ Delay",
                    f"{delay} min" if delay is not None else "N/A"
                )

            with t3:
                st.metric(
                    "📍 Segment Progress",
                    f"{progress}%" if progress is not None else "N/A"
                )

        else:
            st.info(
                "Location data available hai, "
                "lekin displayable fields nahi mile."
            )


# =========================================================
# OTHER LIVE DATA
# =========================================================

extra_sections = [
    ("🚉 Next Halting Station", "nextHaltingStation"),
    ("🛤️ Route / Station Information", "stations"),
    ("⚠️ Diversion Information", "diversion"),
    ("🚫 Cancellation Information", "cancellation")
]

for section_title, data_key in extra_sections:

    section_data = data.get(data_key)

    if section_data:

        st.subheader(section_title)

        if isinstance(section_data, dict):

            rows = flatten_dict(section_data)

            if rows:
                st.dataframe(
                    pd.DataFrame(rows),
                    use_container_width=True,
                    hide_index=True
                )
            else:
                st.write(section_data)

        elif isinstance(section_data, list):

            if section_data and all(
                isinstance(item, dict)
                for item in section_data
            ):
                st.dataframe(
                    pd.DataFrame(section_data),
                    use_container_width=True,
                    hide_index=True
                )
            else:
                st.write(section_data)

        else:
            st.write(section_data)


# =========================================================
# PYSPARK PROCESSING
# =========================================================

st.subheader("⚡ Big Data Processing with PySpark")

st.caption(
    "Live railway data is converted into a Spark DataFrame "
    "for Big Data processing."
)

try:

    # -----------------------------------------------------
    # CREATE SPARK SESSION
    # -----------------------------------------------------

    spark = (
        SparkSession.builder
        .appName("RailwayLiveAnalytics")
        .master("local[*]")
        .config("spark.ui.enabled", "false")
        .config("spark.driver.bindAddress", "127.0.0.1")
        .getOrCreate()
    )

    spark.sparkContext.setLogLevel("ERROR")


    # -----------------------------------------------------
    # CREATE RECORD
    # -----------------------------------------------------

    spark_records = [
        {
            "train_number": str(number),
            "train_name": str(name),
            "train_type": str(train_type),
            "category": str(category),
            "journey_date": str(start_date),
            "status": str(status),
            "last_updated": str(updated)
        }
    ]


    # -----------------------------------------------------
    # SPARK DATAFRAME
    # -----------------------------------------------------

    spark_df = spark.createDataFrame(
        spark_records
    )

    st.success(
        "✅ PySpark DataFrame created successfully."
    )


    # -----------------------------------------------------
    # DISPLAY SPARK DATA
    # -----------------------------------------------------

    # Keep one Spark action only.
    # This is more reliable on Streamlit Cloud.
    spark_display = spark_df.toPandas()

    st.dataframe(
        spark_display,
        use_container_width=True,
        hide_index=True
    )


    # -----------------------------------------------------
    # CLOUD-SAFE ANALYTICS
    # -----------------------------------------------------

    # Do NOT call spark_df.count() or
    # distinct().count() here.
    # Those extra Spark actions were causing the
    # SparkThreadUtils / JobAbortedException on Cloud.

    record_count = len(spark_display)

    unique_trains = (
        spark_display["train_number"]
        .astype(str)
        .nunique()
    )


    a1, a2, a3 = st.columns(3)

    with a1:
        st.metric(
            "Spark Records",
            record_count
        )

    with a2:
        st.metric(
            "Unique Trains",
            unique_trains
        )

    with a3:
        st.metric(
            "Engine",
            "PySpark"
        )


    # -----------------------------------------------------
    # SCHEMA
    # -----------------------------------------------------

    with st.expander("🔎 View Spark DataFrame Schema"):

        schema_rows = []

        for field in spark_df.schema.fields:

            schema_rows.append(
                {
                    "Column": field.name,
                    "Data Type": str(field.dataType),
                    "Nullable": field.nullable
                }
            )

        st.dataframe(
            pd.DataFrame(schema_rows),
            use_container_width=True,
            hide_index=True
        )


    # -----------------------------------------------------
    # STOP SPARK
    # -----------------------------------------------------

    spark.stop()


except Exception as e:

    st.warning(
        "⚠️ PySpark processing issue detected."
    )

    st.caption(
        "API aur dashboard data available hai, "
        "lekin Spark runtime me problem aa rahi hai."
    )

    with st.expander("Technical Error"):

        st.code(
            str(e),
            language="text"
        )


# =========================================================
# PROJECT INFORMATION
# =========================================================

st.divider()

st.subheader("ℹ️ About This Project")

about1, about2 = st.columns(2)

with about1:

    st.write("**Data Source**")
    st.write("RailRadar Real-Time Railway API")

    st.write("**Processing Engine**")
    st.write("Apache PySpark")


with about2:

    st.write("**Visualization**")
    st.write("Streamlit")

    st.write("**Programming Language**")
    st.write("Python")


# =========================================================
# FOOTER
# =========================================================

st.divider()

st.caption(
    "🚆 Railway Live Analytics • "
    "RailRadar API • PySpark • Streamlit"
)
