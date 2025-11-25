"""
Simple Web Dashboard for Stock Analysis Extension
A lightweight Dash-based dashboard for visualizing analysis results
"""

import dash
from dash import dcc, html, Input, Output, dash_table
import plotly.express as px
import plotly.graph_objects as go
import pandas as pd
import numpy as np
from datetime import datetime, timedelta
import sys
from pathlib import Path

# Add modules to path
sys.path.append(str(Path(__file__).parent / "modules"))

from main import StockAnalysisApp

class StockAnalysisDashboard:
    def __init__(self, config_path: str = None):
        """Initialize the dashboard with stock analysis app"""
        self.app = dash.Dash(__name__)
        self.stock_app = StockAnalysisApp(config_path)
        
        # Initialize the stock analysis system
        if not self.stock_app.initialize():
            print("Warning: Stock analysis system initialization failed")
        
        # Setup layout and callbacks
        self.setup_layout()
        self.setup_callbacks()
    
    def setup_layout(self):
        """Setup the dashboard layout"""
        self.app.layout = html.Div([
            html.Div([
                html.H1("Stock Analysis Dashboard", className="header-title"),
                html.P("Comprehensive Stock Analysis and Portfolio Management", 
                       className="header-subtitle")
            ], className="header"),
            
            dcc.Tabs(id="main-tabs", value="analysis-tab", children=[
                dcc.Tab(label="Stock Analysis", value="analysis-tab"),
                dcc.Tab(label="Portfolio Summary", value="portfolio-tab"),
                dcc.Tab(label="Recommendations", value="recommendations-tab"),
                dcc.Tab(label="Performance", value="performance-tab")
            ]),
            
            html.Div(id="tab-content")
        ])
    
    def setup_callbacks(self):
        """Setup dashboard callbacks"""
        
        @self.app.callback(
            Output("tab-content", "children"),
            Input("main-tabs", "value")
        )
        def render_tab_content(active_tab):
            if active_tab == "analysis-tab":
                return self.analysis_tab_layout()
            elif active_tab == "portfolio-tab":
                return self.portfolio_tab_layout()
            elif active_tab == "recommendations-tab":
                return self.recommendations_tab_layout()
            elif active_tab == "performance-tab":
                return self.performance_tab_layout()
            return html.Div("Select a tab")
        
        @self.app.callback(
            Output("analysis-results", "children"),
            Input("analyze-button", "n_clicks"),
            Input("stock-symbol", "value")
        )
        def analyze_stock(n_clicks, symbol):
            if n_clicks and symbol:
                try:
                    result = self.stock_app.analyze_stock(symbol.upper())
                    return self.format_analysis_result(result)
                except Exception as e:
                    return html.Div(f"Error analyzing {symbol}: {str(e)}", 
                                   style={"color": "red"})
            return html.Div("Enter a symbol and click Analyze")
        
        @self.app.callback(
            Output("recommendations-content", "children"),
            Input("get-recommendations-button", "n_clicks"),
            Input("max-recommendations", "value")
        )
        def get_recommendations(n_clicks, max_count):
            if n_clicks:
                try:
                    recommendations = self.stock_app.get_recommendations(max_count or 10)
                    return self.format_recommendations(recommendations)
                except Exception as e:
                    return html.Div(f"Error getting recommendations: {str(e)}", 
                                   style={"color": "red"})
            return html.Div("Click to get recommendations")
    
    def analysis_tab_layout(self):
        """Layout for stock analysis tab"""
        return html.Div([
            html.H2("Individual Stock Analysis"),
            
            html.Div([
                html.Div([
                    html.Label("Stock Symbol:"),
                    dcc.Input(
                        id="stock-symbol",
                        type="text",
                        placeholder="Enter symbol (e.g., AAPL)",
                        style={"width": "200px", "margin": "10px"}
                    ),
                    html.Button("Analyze", id="analyze-button", n_clicks=0,
                               style={"margin": "10px"})
                ], style={"display": "flex", "align-items": "center"}),
                
                html.Div(id="analysis-results", style={"margin-top": "20px"})
            ])
        ])
    
    def portfolio_tab_layout(self):
        """Layout for portfolio summary tab"""
        try:
            portfolio_summary = self.stock_app.get_portfolio_summary()
            
            return html.Div([
                html.H2("Portfolio Summary"),
                
                # Portfolio metrics cards
                html.Div([
                    self.create_metric_card("Total Portfolio Value", 
                                          f"${portfolio_summary.get('total_portfolio_value', 0):,.2f}"),
                    self.create_metric_card("Cash Balance", 
                                          f"${portfolio_summary.get('cash_balance', 0):,.2f}"),
                    self.create_metric_card("Positions Value", 
                                          f"${portfolio_summary.get('total_positions_value', 0):,.2f}"),
                    self.create_metric_card("Unrealized P&L", 
                                          f"${portfolio_summary.get('unrealized_pnl', 0):,.2f}")
                ], style={"display": "flex", "justify-content": "space-around", "margin": "20px 0"}),
                
                # Holdings table
                html.H3("Top Holdings"),
                self.create_holdings_table(portfolio_summary.get('top_holdings', [])),
                
                # Sector allocation chart
                html.H3("Sector Allocation"),
                self.create_sector_chart(portfolio_summary.get('sector_allocation', {}))
            ])
            
        except Exception as e:
            return html.Div(f"Error loading portfolio: {str(e)}", style={"color": "red"})
    
    def recommendations_tab_layout(self):
        """Layout for recommendations tab"""
        return html.Div([
            html.H2("Stock Recommendations"),
            
            html.Div([
                html.Label("Maximum Recommendations:"),
                dcc.Input(
                    id="max-recommendations",
                    type="number",
                    value=10,
                    min=1,
                    max=50,
                    style={"width": "100px", "margin": "10px"}
                ),
                html.Button("Get Recommendations", id="get-recommendations-button", 
                           n_clicks=0, style={"margin": "10px"})
            ], style={"display": "flex", "align-items": "center"}),
            
            html.Div(id="recommendations-content", style={"margin-top": "20px"})
        ])
    
    def performance_tab_layout(self):
        """Layout for performance metrics tab"""
        return html.Div([
            html.H2("Performance Metrics"),
            html.P("Performance metrics will be displayed here when trade data is available."),
            
            # Placeholder for performance charts
            html.Div([
                dcc.Graph(
                    figure=go.Figure().add_annotation(
                        text="Performance data will appear here<br>when trade history is available",
                        xref="paper", yref="paper",
                        x=0.5, y=0.5, xanchor='center', yanchor='middle',
                        showarrow=False, font_size=16
                    ).update_layout(
                        title="Portfolio Performance Over Time",
                        xaxis=dict(visible=False),
                        yaxis=dict(visible=False)
                    )
                )
            ])
        ])
    
    def create_metric_card(self, title: str, value: str):
        """Create a metric card component"""
        return html.Div([
            html.H4(title, style={"margin": "0", "color": "#666"}),
            html.H2(value, style={"margin": "5px 0", "color": "#333"})
        ], style={
            "border": "1px solid #ddd",
            "border-radius": "8px",
            "padding": "20px",
            "text-align": "center",
            "background": "#f9f9f9",
            "min-width": "200px"
        })
    
    def create_holdings_table(self, holdings: list):
        """Create holdings table"""
        if not holdings:
            return html.Div("No holdings data available")
        
        df = pd.DataFrame(holdings)
        
        return dash_table.DataTable(
            data=df.to_dict('records'),
            columns=[
                {"name": "Symbol", "id": "symbol"},
                {"name": "Company", "id": "company_name"},
                {"name": "Quantity", "id": "quantity", "type": "numeric"},
                {"name": "Value", "id": "position_value", "type": "numeric"},
                {"name": "P&L", "id": "unrealized_pnl", "type": "numeric"}
            ],
            style_cell={'textAlign': 'left'},
            style_data_conditional=[
                {
                    'if': {'filter_query': '{unrealized_pnl} > 0'},
                    'backgroundColor': '#d4edda',
                    'color': 'black',
                },
                {
                    'if': {'filter_query': '{unrealized_pnl} < 0'},
                    'backgroundColor': '#f8d7da',
                    'color': 'black',
                }
            ]
        )
    
    def create_sector_chart(self, sector_allocation: dict):
        """Create sector allocation pie chart"""
        if not sector_allocation:
            return html.Div("No sector data available")
        
        fig = px.pie(
            values=list(sector_allocation.values()),
            names=list(sector_allocation.keys()),
            title="Portfolio Sector Allocation"
        )
        
        return dcc.Graph(figure=fig)
    
    def format_analysis_result(self, result: dict):
        """Format analysis result for display"""
        if result.get('error'):
            return html.Div([
                html.H3(f"Error analyzing {result['symbol']}", style={"color": "red"}),
                html.P(result['error'])
            ])
        
        analysis = result['analysis']
        stock_data = result.get('stock_data', {})
        
        # Get current price
        current_price = "N/A"
        if stock_data.get('price_data') is not None and not stock_data['price_data'].empty:
            current_price = f"${float(stock_data['price_data']['close'].iloc[-1]):.2f}"
        
        # Company info
        company_name = stock_data.get('fundamentals', {}).get('company_name', analysis['symbol'])
        sector = stock_data.get('fundamentals', {}).get('sector', 'Unknown')
        
        # Create score gauge
        score_fig = go.Figure(go.Indicator(
            mode="gauge+number",
            value=analysis['overall_score'],
            domain={'x': [0, 1], 'y': [0, 1]},
            title={'text': "Overall Score"},
            gauge={
                'axis': {'range': [None, 100]},
                'bar': {'color': "darkgreen"},
                'steps': [
                    {'range': [0, 35], 'color': "lightgray"},
                    {'range': [35, 65], 'color': "yellow"},
                    {'range': [65, 80], 'color': "lightgreen"},
                    {'range': [80, 100], 'color': "green"}
                ],
                'threshold': {
                    'line': {'color': "red", 'width': 4},
                    'thickness': 0.75,
                    'value': 90
                }
            }
        ))
        
        # Component scores chart
        scores_df = pd.DataFrame({
            'Component': ['Fundamental', 'Technical', 'Momentum', 'Sentiment'],
            'Score': [
                analysis['fundamental_score'],
                analysis['technical_score'],
                analysis['momentum_score'],
                analysis['sentiment_score']
            ]
        })
        
        scores_fig = px.bar(scores_df, x='Component', y='Score', 
                           title="Component Scores",
                           color='Score',
                           color_continuous_scale='RdYlGn')
        
        return html.Div([
            html.Div([
                html.H3(f"{analysis['symbol']} - {company_name}"),
                html.P(f"Sector: {sector} | Current Price: {current_price}")
            ]),
            
            html.Div([
                html.Div([
                    dcc.Graph(figure=score_fig, style={"height": "300px"})
                ], style={"width": "48%", "display": "inline-block"}),
                
                html.Div([
                    dcc.Graph(figure=scores_fig, style={"height": "300px"})
                ], style={"width": "48%", "float": "right", "display": "inline-block"})
            ]),
            
            html.Div([
                html.Div([
                    html.H4("Analysis Summary"),
                    html.P(f"Recommendation: {analysis['recommendation']}"),
                    html.P(f"Risk Rating: {analysis['risk_rating']}"),
                    html.P(f"Confidence: {analysis['confidence_level']:.1f}%"),
                    html.P(f"Target Price: ${analysis['target_price']:.2f}" if analysis['target_price'] else "Target Price: N/A")
                ], style={"width": "48%", "display": "inline-block", "vertical-align": "top"}),
                
                html.Div([
                    html.H4("Key Metrics"),
                    html.P(f"Analysis Date: {analysis['analysis_date']}"),
                    html.P(f"Overall Score: {analysis['overall_score']:.1f}/100"),
                    html.P(f"Fundamental: {analysis['fundamental_score']:.1f}"),
                    html.P(f"Technical: {analysis['technical_score']:.1f}")
                ], style={"width": "48%", "float": "right", "display": "inline-block", "vertical-align": "top"})
            ])
        ])
    
    def format_recommendations(self, recommendations: list):
        """Format recommendations for display"""
        if not recommendations:
            return html.Div("No recommendations available")
        
        # Create recommendations table
        df = pd.DataFrame(recommendations)
        
        return html.Div([
            dash_table.DataTable(
                data=df.to_dict('records'),
                columns=[
                    {"name": "Symbol", "id": "symbol"},
                    {"name": "Company", "id": "company_name"},
                    {"name": "Score", "id": "score", "type": "numeric", "format": {"specifier": ".1f"}},
                    {"name": "Recommendation", "id": "recommendation"},
                    {"name": "Current Price", "id": "current_price", "type": "numeric"},
                    {"name": "Target Price", "id": "target_price", "type": "numeric"},
                    {"name": "Risk", "id": "risk_rating"},
                    {"name": "Sector", "id": "sector"}
                ],
                style_cell={'textAlign': 'left'},
                style_data_conditional=[
                    {
                        'if': {'filter_query': '{recommendation} = STRONG_BUY'},
                        'backgroundColor': '#d4edda',
                        'color': 'black',
                    },
                    {
                        'if': {'filter_query': '{recommendation} = BUY'},
                        'backgroundColor': '#d1ecf1',
                        'color': 'black',
                    }
                ],
                sort_action="native",
                page_size=20
            )
        ])
    
    def run(self, debug=True, host='127.0.0.1', port=8050):
        """Run the dashboard"""
        print(f"Starting Stock Analysis Dashboard on http://{host}:{port}")
        self.app.run_server(debug=debug, host=host, port=port)

# Add some basic CSS styling
external_stylesheets = ['https://codepen.io/chriddyp/pen/bWLwgP.css']

def main():
    """Run the dashboard"""
    try:
        from dash.dash_table.Format import Format as FormatTemplate
    except ImportError:
        # Fallback for older Dash versions
        class FormatTemplate:
            @staticmethod
            def money(decimals):
                return {"type": "numeric", "format": f"$.{decimals}f"}
    
    # Create and run dashboard
    dashboard = StockAnalysisDashboard()
    dashboard.run(debug=False, host='0.0.0.0', port=8050)

if __name__ == "__main__":
    main()
