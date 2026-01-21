import json
import re
import logging
import random
import os
import shutil
from typing import Dict, Any, List, Optional, Union, TYPE_CHECKING
from decimal import Decimal
import datetime

import google.generativeai as genai
from django.conf import settings
from django.db import transaction
from django.core.files import File

logger = logging.getLogger(__name__)

# NOTE: AI Logic moved to apps.ged.services to respect Domain Driven Design.