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

    /* Main background */
    .stApp {
        background-color: #080b12;
    }

    /* Main content width */
    .block-container {
        max-width: 1400px;
        padding-top: 2rem;
        padding-bottom: 3rem;
    }

    /* Header */
    .app-header {
        background: linear-gradient(
            135deg,
            #111827,
            #172554
        );
        border: 1px solid #263248;
        border-radius: 20px;
        padding: 28px;
        margin-bottom: 25px;
    }

    /* Search area */
    .search-box {
        background: #111827;
        border: 1px solid #263248;
        border-radius: 18px;
        padding: 22px;
        margin-bottom: 25px;
    }

    /* Train card */
    .train-box {
        background: linear-gradient(
            135deg,
            #111827,
            #172554
        );
        border: 1px solid #29446f;
        border-radius: 20px;
        padding: 24px;
        margin: 20px 0;
    }

    /* Status */
    .running-status {
        background: #052e1b;
        border: 1px solid #166534;
        color: #4ade80;
        border-radius: 12px;
        padding: 12px;
        text-align: center;
        font-weight: bold;
    }

    .notstarted-status {
        background: #332400;
        border: 1px solid #92400e;
        color: #fbbf24;
        border-radius: 12px;
        padding: 12px;
        text-align: center;
        font-weight: bold;
    }

    .normal-status {
        background: #172554;
        border: 1px solid #1d4ed8;
        color: #60a5fa;
        border-radius: 12px;
        padding: 12px;
        text-align: center;
        font-weight: bold;
    }

    /* Footer */
    .footer-text {
        text-align: center;
        color: #64748b;
        padding-top: 30px;
        margin-top: 40px;
        border-top: 1px solid #1e293b;
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
        "Project folder me .env file check karo."
    )

    st.code(
        "RAILRADAR_API_KEY=YOUR_API_KEY",
        language="text"
    )

    st.stop()


# =========================================================
# API FUNCTION
# =========================================================

def get_train_data(train_number):

    url = API_URL.format(
        number=train_number
    )

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

        if response.status_code == 404:

            return False, "Train data not found."

        if response.status_code == 429:

            return False, "API request limit exceeded."

        if response.status_code >= 500:

            return False, "RailRadar server is temporarily unavailable."

        return False, (
            f"API Error {response.status_code}"
        )

    except requests.exceptions.Timeout:

        return False, "Request timeout ho gaya."

    except requests.exceptions.ConnectionError:

        return False, "Internet connection problem."

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
# SEARCH SECTION
# =========================================================

st.subheader("🔎 Search Live Train")

col1, col2 = st.columns(
    [4, 1],
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


# =========================================================
# EMPTY STATE
# =========================================================

if not search_button:

    st.info(
        "Enter a train number and click Search "
        "to fetch real-time railway data."
    )

    st.markdown("### 📌 Project Features")

    f1, f2, f3, f4 = st.columns(4)

    with f1:
        st.metric(
            "🚆 Live Tracking",
            "Real-Time"
        )

    with f2:
        st.metric(
            "⚡ Processing",
            "PySpark"
        )

    with f3:
        st.metric(
            "📡 Data Source",
            "RailRadar"
        )

    with f4:
        st.metric(
            "📊 Dashboard",
            "Streamlit"
        )


    st.stop()


# =========================================================
# INPUT VALIDATION
# =========================================================

if not train_number:

    st.warning(
        "⚠️ Please enter a train number."
    )

    st.stop()


if not train_number.isdigit():

    st.warning(
        "⚠️ Train number must contain only digits."
    )

    st.stop()


# =========================================================
# API REQUEST
# =========================================================

with st.spinner(
    "🔄 Fetching real-time railway data..."
):

    success, result = get_train_data(
        train_number
    )


# =========================================================
# ERROR
# =========================================================

if not success:

    st.error(
        f"❌ {result}"
    )

    st.stop()


# =========================================================
# EXTRACT MAIN DATA
# =========================================================

data = result.get(
    "data",
    {}
)


if not isinstance(data, dict):

    st.error(
        "Unexpected API response received."
    )

    st.stop()


# =========================================================
# TRAIN DATA
# =========================================================

number = (
    get_value(
        data,
        "trainNumber"
    )
    or train_number
)

name = (
    get_value(
        data,
        "trainName"
    )
    or "Unknown Train"
)

start_date = (
    get_value(
        data,
        "startDate"
    )
    or "N/A"
)

status = (
    get_value(
        data,
        "status"
    )
    or "Unknown"
)

updated = (
    get_value(
        data,
        "lastUpdatedAt"
    )
    or "N/A"
)


# =========================================================
# TRAIN INFORMATION
# =========================================================

train_info = data.get(
    "train",
    {}
)

if not isinstance(
    train_info,
    dict
):

    train_info = {}


train_type = (
    get_value(
        train_info,
        "type"
    )
    or "N/A"
)

category = (
    get_value(
        train_info,
        "category"
    )
    or "N/A"
)


# =========================================================
# SUCCESS MESSAGE
# =========================================================

st.success(
    "✅ Live railway data received successfully."
)


# =========================================================
# TRAIN HEADER
# =========================================================

st.markdown(
    f"## 🚆 {name}"
)

st.caption(
    f"Train Number: {number}  •  "
    f"{train_type}  •  {category}"
)


# =========================================================
# STATUS
# =========================================================

status_lower = str(
    status
).lower()


if status_lower in [
    "running",
    "started",
    "active"
]:

    st.success(
        "🟢 TRAIN IS CURRENTLY RUNNING"
    )

elif status_lower in [
    "not-started",
    "not_started"
]:

    st.warning(
        "🟡 TRAIN HAS NOT STARTED YET"
    )

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
        f"📅 **Journey Date**\n\n"
        f"{start_date}"
    )

with j2:

    st.info(
        f"🔄 **Last Updated**\n\n"
        f"{updated}"
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
# CURRENT LOCATION
# =========================================================

current_location = data.get(
    "currentLocation"
)


if current_location:

    st.subheader(
        "📍 Current Location & Telemetry"
    )

    if isinstance(
        current_location,
        dict
    ):

        location_rows = []

        for key, value in current_location.items():

            if isinstance(
                value,
                (dict, list)
            ):

                continue

            location_rows.append(
                {
                    "Parameter": key,
                    "Value": value
                }
            )

        if location_rows:

            location_df = pd.DataFrame(
                location_rows
            )

            st.dataframe(
                location_df,
                use_container_width=True,
                hide_index=True
            )

        else:

            st.info(
                "Location data nested format me available hai."
            )


# =========================================================
# PYSPARK PROCESSING
# =========================================================

st.subheader(
    "⚡ Big Data Processing with PySpark"
)

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
        .appName(
            "RailwayLiveAnalytics"
        )
        .master("local[*]")
        .getOrCreate()
    )

    spark.sparkContext.setLogLevel(
        "ERROR"
    )


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

    spark_display = spark_df.toPandas()

    st.dataframe(
        spark_display,
        use_container_width=True,
        hide_index=True
    )


    # -----------------------------------------------------
    # PYSPARK ANALYTICS
    # -----------------------------------------------------

    record_count = spark_df.count()

    unique_trains = (
        spark_df
        .select("train_number")
        .distinct()
        .count()
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
    # STOP SPARK
    # -----------------------------------------------------

    spark.stop()


except Exception as e:

    st.warning(
        f"⚠️ PySpark processing issue: {e}"
    )


# =========================================================
# PROJECT INFORMATION
# =========================================================

st.divider()

st.subheader("ℹ️ About This Project")

about1, about2 = st.columns(2)

with about1:

    st.write(
        "**Data Source**"
    )

    st.write(
        "RailRadar Real-Time Railway API"
    )

    st.write(
        "**Processing Engine**"
    )

    st.write(
        "Apache PySpark"
    )


with about2:

    st.write(
        "**Visualization**"
    )

    st.write(
        "Streamlit"
    )

    st.write(
        "**Programming Language**"
    )

    st.write(
        "Python"
    )


# =========================================================
# FOOTER
# =========================================================

st.divider()

st.caption(
    "🚆 Railway Live Analytics • "
    "RailRadar API • PySpark • Streamlit"
)