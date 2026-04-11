<div style="background:#ffffff;padding:10px;">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #fff;
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .header {
            margin-bottom: 30px;
            border-bottom: 3px solid #333;
            padding-bottom: 20px;
        }
        
        .header-content {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .site-logo {
            flex-shrink: 0;
        }
        
        .logo-img {
            max-height: 60px;
            max-width: 200px;
            height: auto;
            width: auto;
        }
        
        .header-text {
            flex: 1;
        }
        
        .header h1 {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .site-name {
            font-size: 16px;
            color: #666;
            margin: 0;
        }
        
        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            gap: 20px;
        }
        
        .info-box {
            border: 2px solid #333;
            padding: 10px;
            min-width: 200px;
        }
        
        .info-box label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        .info-box input {
            width: 100%;
            border: none;
            border-bottom: 1px solid #666;
            font-size: 16px;
            padding: 2px 0;
        }
        
        .section {
            margin-bottom: 30px;
        }
        
        .section h2 {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 15px;
            border-bottom: 2px solid #333;
            padding-bottom: 5px;
        }
        
        .task-list {
            list-style: none;
        }
        
        .task-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 8px;
            padding: 8px;
            border: 1px solid #ddd;
            background: #f9f9f9;
        }
        
        .task-checkbox {
            margin-right: 12px;
            margin-top: 2px;
            min-width: 16px;
            height: 16px;
        }
        
        .task-text {
            flex: 1;
            font-size: 14px;
        }
        
        .pastor-note {
            background: #fff3cd;
            border: 2px solid #ffc107;
            padding: 15px;
            margin-top: 30px;
            border-radius: 5px;
        }
        
        .pastor-note h3 {
            color: #856404;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .pastor-note p {
            color: #856404;
            font-style: italic;
            font-size: 14px;
        }
        
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .print-button:hover {
            background: #0056b3;
        }
        
        @media print {
            .print-button {
                display: none;
            }
            
            body {
                padding: 10px;
            }
            
            .task-item {
                page-break-inside: avoid;
            }
        }
        
        @media screen and (max-width: 600px) {
            .info-section {
                flex-direction: column;
            }
            
            .info-box {
                min-width: auto;
            }
        }
    </style>
    <button class="print-button" onclick="window.print()">Print Checklist</button>
    
    <div class="header">
        <div class="header-content">

            <div class="header-text">
                <h1>Cleaning Checklist</h1>

            </div>
        </div>
    </div>
    
    <div class="info-section">
        <div class="info-box">
            <label for="custodian">Today's Custodian:</label>
            <input type="text" id="custodian" placeholder="Enter name">
        </div>
        <div class="info-box">
            <label for="date">Date:</label>
            <input type="text" id="date" placeholder="{{ date('F j, Y') }}">
        </div>
    </div>
    
    <div class="section">
        <h2>Regular Tasks</h2>
        <ul class="task-list">
            @foreach ($regularTasks as $task)
                <li class="task-item">
                    <input type="checkbox" class="task-checkbox">
                    <span class="task-text">{{ $task }}</span>
                </li>
            @endforeach
        </ul>
    </div>
    
    <div class="section">
        <h2>Extras that don't have to be done every week</h2>
        <ul class="task-list">
            @foreach ($extraTasks as $task)
                <li class="task-item">
                    <input type="checkbox" class="task-checkbox">
                    <span class="task-text">{{ $task }}</span>
                </li>
            @endforeach
        </ul>
    </div>
    
    <script>
        // Auto-fill today's date
        document.getElementById('date').value = new Date().toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        });
        
        // Prevent accidental form submission
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
            }
        });
    </script>
</div>