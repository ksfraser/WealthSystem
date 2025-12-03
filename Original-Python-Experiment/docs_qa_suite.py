#!/usr/bin/env python3
"""
Documentation Quality Assurance and Testing Suite
Automated testing for documentation quality, link checking, and format validation.

Usage:
    python docs_qa_suite.py [options]
    
Options:
    --check-links      Check all internal and external links
    --validate-format  Validate markdown format and structure
    --check-images     Verify all image references
    --spell-check      Run spell checking (requires aspell or hunspell)
    --full-audit       Run complete documentation audit
    --fix-links        Attempt to fix broken internal links
    --generate-report  Generate comprehensive quality report
"""

import os
import re
import sys
import json
import urllib.request
import urllib.parse
from pathlib import Path
from collections import defaultdict, Counter
from datetime import datetime
import subprocess
import argparse

class DocumentationQA:
    def __init__(self, root_dir='.'):
        self.root_dir = Path(root_dir)
        self.docs_dir = self.root_dir / 'docs'
        self.project_dir = self.root_dir / 'project'
        
        # Results storage
        self.results = {
            'link_check': {'passed': [], 'failed': [], 'warnings': []},
            'format_check': {'passed': [], 'failed': [], 'warnings': []},
            'image_check': {'passed': [], 'failed': [], 'warnings': []},
            'spell_check': {'errors': [], 'suggestions': []},
            'structure_check': {'passed': [], 'failed': [], 'warnings': []},
            'metadata': {
                'scan_time': datetime.now().isoformat(),
                'total_files': 0,
                'total_links': 0,
                'total_images': 0
            }
        }
        
        # File patterns to check
        self.markdown_patterns = ['*.md', '*.markdown']
        self.html_patterns = ['*.html', '*.htm']
        self.image_patterns = ['*.png', '*.jpg', '*.jpeg', '*.gif', '*.svg', '*.webp']
        
        # Common words dictionary for spell checking
        self.technical_words = {
            'api', 'apis', 'json', 'xml', 'yaml', 'yml', 'http', 'https', 'url', 'uri',
            'php', 'javascript', 'js', 'css', 'html', 'sql', 'mysql', 'mariadb',
            'jwt', 'oauth', 'rest', 'restful', 'crud', 'uuid', 'guid',
            'chatgpt', 'microcap', 'fintech', 'portfolio', 'portfolios',
            'phpdocumentor', 'swagger', 'openapi', 'postman', 'insomnia',
            'github', 'git', 'cli', 'sdk', 'sdks', 'namespace', 'namespaces',
            'psr', 'autoload', 'autoloading', 'composer', 'vendor',
            'backend', 'frontend', 'fullstack', 'middleware', 'endpoint', 'endpoints',
            'dockerfile', 'kubernetes', 'docker', 'containerization',
            'regex', 'boolean', 'varchar', 'datetime', 'timestamp',
            'microcap', 'crypto', 'cryptocurrency', 'blockchain', 'defi'
        }

    def find_markdown_files(self):
        """Find all markdown files in the documentation directories."""
        files = []
        for pattern in self.markdown_patterns:
            files.extend(list(self.root_dir.rglob(pattern)))
        return [f for f in files if not any(skip in str(f) for skip in ['node_modules', 'vendor', '.git', '__pycache__'])]

    def find_html_files(self):
        """Find all HTML files in the documentation directories."""
        files = []
        for pattern in self.html_patterns:
            files.extend(list(self.root_dir.rglob(pattern)))
        return [f for f in files if not any(skip in str(f) for skip in ['node_modules', 'vendor', '.git', '__pycache__'])]

    def extract_links_from_markdown(self, content):
        """Extract all links from markdown content."""
        # Markdown links: [text](url)
        markdown_links = re.findall(r'\[([^\]]*)\]\(([^)]+)\)', content)
        
        # Reference links: [text][ref] and [ref]: url
        ref_links = re.findall(r'\[([^\]]*)\]:\s*([^\s]+)', content)
        
        # HTML links in markdown
        html_links = re.findall(r'<a[^>]+href=["\']([^"\']+)["\'][^>]*>', content, re.IGNORECASE)
        
        all_links = []
        all_links.extend([(text, url) for text, url in markdown_links])
        all_links.extend([(text, url) for text, url in ref_links])
        all_links.extend([('', url) for url in html_links])
        
        return all_links

    def extract_images_from_markdown(self, content):
        """Extract all image references from markdown content."""
        # Markdown images: ![alt](src)
        markdown_images = re.findall(r'!\[([^\]]*)\]\(([^)]+)\)', content)
        
        # HTML images in markdown
        html_images = re.findall(r'<img[^>]+src=["\']([^"\']+)["\'][^>]*>', content, re.IGNORECASE)
        
        all_images = []
        all_images.extend([(alt, src) for alt, src in markdown_images])
        all_images.extend([('', src) for src in html_images])
        
        return all_images

    def check_internal_link(self, link_url, current_file):
        """Check if an internal link is valid."""
        if link_url.startswith(('http://', 'https://', 'mailto:', 'tel:', 'ftp://')):
            return True, "External link"
        
        if link_url.startswith('#'):
            return True, "Anchor link"
        
        # Handle relative paths
        if link_url.startswith('./') or link_url.startswith('../'):
            target_path = (current_file.parent / link_url).resolve()
        else:
            target_path = (self.root_dir / link_url).resolve()
        
        # Check if file exists
        if target_path.exists():
            return True, f"Valid path: {target_path}"
        
        # Check if it's a directory index
        if (target_path / 'index.html').exists():
            return True, f"Directory index: {target_path}/index.html"
        
        if (target_path / 'README.md').exists():
            return True, f"Directory readme: {target_path}/README.md"
        
        return False, f"File not found: {target_path}"

    def check_external_link(self, url, timeout=10):
        """Check if an external link is accessible."""
        try:
            req = urllib.request.Request(url, headers={
                'User-Agent': 'Documentation-QA-Bot/1.0 (ChatGPT-Micro-Cap-Experiment)'
            })
            
            with urllib.request.urlopen(req, timeout=timeout) as response:
                status_code = response.getcode()
                if 200 <= status_code < 400:
                    return True, f"HTTP {status_code}"
                else:
                    return False, f"HTTP {status_code}"
                    
        except urllib.error.HTTPError as e:
            return False, f"HTTP Error: {e.code} {e.reason}"
        except urllib.error.URLError as e:
            return False, f"URL Error: {e.reason}"
        except Exception as e:
            return False, f"Error: {str(e)}"

    def check_links(self, check_external=True):
        """Check all links in markdown files."""
        print("🔗 Checking documentation links...")
        
        markdown_files = self.find_markdown_files()
        self.results['metadata']['total_files'] = len(markdown_files)
        
        total_links = 0
        
        for file_path in markdown_files:
            try:
                with open(file_path, 'r', encoding='utf-8') as f:
                    content = f.read()
                
                links = self.extract_links_from_markdown(content)
                total_links += len(links)
                
                for link_text, link_url in links:
                    # Skip empty links
                    if not link_url.strip():
                        continue
                    
                    # Check internal vs external
                    if link_url.startswith(('http://', 'https://')):
                        if check_external:
                            is_valid, message = self.check_external_link(link_url)
                            result = {
                                'file': str(file_path),
                                'link': link_url,
                                'text': link_text,
                                'message': message,
                                'type': 'external'
                            }
                        else:
                            # Skip external link checking
                            result = {
                                'file': str(file_path),
                                'link': link_url,
                                'text': link_text,
                                'message': 'External link (not checked)',
                                'type': 'external'
                            }
                            is_valid = True
                    else:
                        is_valid, message = self.check_internal_link(link_url, file_path)
                        result = {
                            'file': str(file_path),
                            'link': link_url,
                            'text': link_text,
                            'message': message,
                            'type': 'internal'
                        }
                    
                    if is_valid:
                        self.results['link_check']['passed'].append(result)
                    else:
                        self.results['link_check']['failed'].append(result)
                        
            except Exception as e:
                error = {
                    'file': str(file_path),
                    'link': 'N/A',
                    'text': 'N/A',
                    'message': f'File processing error: {str(e)}',
                    'type': 'error'
                }
                self.results['link_check']['failed'].append(error)
        
        self.results['metadata']['total_links'] = total_links
        
        passed = len(self.results['link_check']['passed'])
        failed = len(self.results['link_check']['failed'])
        
        print(f"   ✅ {passed} links passed")
        print(f"   ❌ {failed} links failed")
        
        return failed == 0

    def check_images(self):
        """Check all image references in documentation."""
        print("🖼️  Checking image references...")
        
        markdown_files = self.find_markdown_files()
        html_files = self.find_html_files()
        all_files = markdown_files + html_files
        
        total_images = 0
        
        for file_path in all_files:
            try:
                with open(file_path, 'r', encoding='utf-8') as f:
                    content = f.read()
                
                if file_path.suffix.lower() in ['.md', '.markdown']:
                    images = self.extract_images_from_markdown(content)
                else:
                    # HTML image extraction
                    images = re.findall(r'<img[^>]+src=["\']([^"\']+)["\'][^>]*>', content, re.IGNORECASE)
                    images = [('', img) for img in images]
                
                total_images += len(images)
                
                for alt_text, img_src in images:
                    if img_src.startswith(('http://', 'https://', 'data:')):
                        # External or data URL image
                        result = {
                            'file': str(file_path),
                            'image': img_src,
                            'alt': alt_text,
                            'message': 'External/data image (not checked)',
                            'type': 'external'
                        }
                        self.results['image_check']['passed'].append(result)
                        continue
                    
                    # Check local image file
                    if img_src.startswith('./') or img_src.startswith('../'):
                        img_path = (file_path.parent / img_src).resolve()
                    else:
                        img_path = (self.root_dir / img_src).resolve()
                    
                    result = {
                        'file': str(file_path),
                        'image': img_src,
                        'alt': alt_text,
                        'path': str(img_path),
                        'type': 'local'
                    }
                    
                    if img_path.exists() and img_path.is_file():
                        result['message'] = f'Image exists: {img_path}'
                        self.results['image_check']['passed'].append(result)
                    else:
                        result['message'] = f'Image not found: {img_path}'
                        self.results['image_check']['failed'].append(result)
                    
                    # Check alt text
                    if not alt_text or alt_text.strip() == '':
                        warning = dict(result)
                        warning['message'] = 'Missing alt text for accessibility'
                        self.results['image_check']['warnings'].append(warning)
                        
            except Exception as e:
                error = {
                    'file': str(file_path),
                    'image': 'N/A',
                    'alt': 'N/A',
                    'message': f'File processing error: {str(e)}',
                    'type': 'error'
                }
                self.results['image_check']['failed'].append(error)
        
        self.results['metadata']['total_images'] = total_images
        
        passed = len(self.results['image_check']['passed'])
        failed = len(self.results['image_check']['failed'])
        warnings = len(self.results['image_check']['warnings'])
        
        print(f"   ✅ {passed} images passed")
        print(f"   ❌ {failed} images failed")
        print(f"   ⚠️  {warnings} warnings")
        
        return failed == 0

    def check_markdown_format(self):
        """Check markdown format and structure."""
        print("📝 Checking markdown format and structure...")
        
        markdown_files = self.find_markdown_files()
        
        for file_path in markdown_files:
            try:
                with open(file_path, 'r', encoding='utf-8') as f:
                    content = f.read()
                    lines = content.split('\n')
                
                issues = []
                
                # Check for consistent heading levels
                headings = []
                for i, line in enumerate(lines, 1):
                    if line.strip().startswith('#'):
                        level = len(line) - len(line.lstrip('#'))
                        headings.append((i, level, line.strip()))
                
                # Check heading hierarchy
                prev_level = 0
                for line_num, level, heading in headings:
                    if level > prev_level + 1:
                        issues.append(f"Line {line_num}: Heading level jump from h{prev_level} to h{level}")
                    prev_level = level
                
                # Check for proper code block formatting
                in_code_block = False
                code_block_lang = None
                for i, line in enumerate(lines, 1):
                    if line.strip().startswith('```'):
                        if not in_code_block:
                            in_code_block = True
                            # Check if language is specified
                            lang_match = re.match(r'```(\w+)', line.strip())
                            code_block_lang = lang_match.group(1) if lang_match else None
                            if not code_block_lang and line.strip() != '```':
                                issues.append(f"Line {i}: Code block without language specification")
                        else:
                            in_code_block = False
                            code_block_lang = None
                
                # Check for trailing whitespace
                for i, line in enumerate(lines, 1):
                    if line.endswith(' ') or line.endswith('\t'):
                        issues.append(f"Line {i}: Trailing whitespace")
                
                # Check for proper list formatting
                for i, line in enumerate(lines, 1):
                    stripped = line.strip()
                    if stripped.startswith(('- ', '* ', '+ ')) or re.match(r'^\d+\. ', stripped):
                        # Check indentation
                        if line.startswith((' ', '\t')) and not line.startswith('    ') and not line.startswith('\t'):
                            if i > 1 and not lines[i-2].strip().startswith(('- ', '* ', '+ ')) and not re.match(r'^\d+\. ', lines[i-2].strip()):
                                issues.append(f"Line {i}: Inconsistent list indentation")
                
                result = {
                    'file': str(file_path),
                    'issues': issues,
                    'headings_count': len(headings),
                    'lines_count': len(lines)
                }
                
                if issues:
                    result['message'] = f"{len(issues)} formatting issues found"
                    self.results['format_check']['failed'].append(result)
                else:
                    result['message'] = "Format validation passed"
                    self.results['format_check']['passed'].append(result)
                    
            except Exception as e:
                error = {
                    'file': str(file_path),
                    'message': f'File processing error: {str(e)}',
                    'issues': [],
                    'headings_count': 0,
                    'lines_count': 0
                }
                self.results['format_check']['failed'].append(error)
        
        passed = len(self.results['format_check']['passed'])
        failed = len(self.results['format_check']['failed'])
        
        print(f"   ✅ {passed} files passed format check")
        print(f"   ❌ {failed} files failed format check")
        
        return failed == 0

    def check_documentation_structure(self):
        """Check overall documentation structure and completeness."""
        print("🏗️  Checking documentation structure...")
        
        required_files = [
            'README.md',
            'docs/README.md',
            'docs/api/README.md',
            'docs/api/API_Documentation.md',
            'project/README.md',
            'docs/Contributing.md'
        ]
        
        recommended_files = [
            'CONTRIBUTING.md',
            'LICENSE',
            'CHANGELOG.md',
            '.gitignore',
            'composer.json'
        ]
        
        # Check required files
        missing_required = []
        for file_path in required_files:
            full_path = self.root_dir / file_path
            if not full_path.exists():
                missing_required.append(file_path)
        
        # Check recommended files
        missing_recommended = []
        for file_path in recommended_files:
            full_path = self.root_dir / file_path
            if not full_path.exists():
                missing_recommended.append(file_path)
        
        # Check directory structure
        expected_dirs = [
            'docs',
            'docs/api',
            'project',
            'project/requirements',
            'project/architecture',
            'project/quality_assurance'
        ]
        
        missing_dirs = []
        for dir_path in expected_dirs:
            full_path = self.root_dir / dir_path
            if not full_path.exists() or not full_path.is_dir():
                missing_dirs.append(dir_path)
        
        # Generate structure report
        structure_issues = []
        
        if missing_required:
            structure_issues.append(f"Missing required files: {', '.join(missing_required)}")
        
        if missing_dirs:
            structure_issues.append(f"Missing directories: {', '.join(missing_dirs)}")
        
        result = {
            'missing_required_files': missing_required,
            'missing_recommended_files': missing_recommended,
            'missing_directories': missing_dirs,
            'issues': structure_issues,
            'score': max(0, 100 - len(missing_required) * 20 - len(missing_dirs) * 10)
        }
        
        if structure_issues:
            result['message'] = f"{len(structure_issues)} structure issues found"
            self.results['structure_check']['failed'].append(result)
        else:
            result['message'] = "Documentation structure is complete"
            self.results['structure_check']['passed'].append(result)
        
        # Warnings for recommended files
        if missing_recommended:
            warning = {
                'message': f"Missing recommended files: {', '.join(missing_recommended)}",
                'files': missing_recommended
            }
            self.results['structure_check']['warnings'].append(warning)
        
        print(f"   📁 Structure completeness: {result['score']}%")
        if missing_required:
            print(f"   ❌ Missing required: {', '.join(missing_required)}")
        if missing_recommended:
            print(f"   ⚠️  Missing recommended: {', '.join(missing_recommended)}")
        
        return len(structure_issues) == 0

    def run_spell_check(self):
        """Run spell checking on documentation files."""
        print("🔤 Running spell check...")
        
        # Check if aspell is available
        try:
            result = subprocess.run(['aspell', '--version'], capture_output=True, text=True)
            if result.returncode != 0:
                raise subprocess.CalledProcessError(result.returncode, 'aspell')
        except (subprocess.CalledProcessError, FileNotFoundError):
            print("   ⚠️  Aspell not found, skipping spell check")
            return True
        
        markdown_files = self.find_markdown_files()
        total_errors = 0
        
        for file_path in markdown_files:
            try:
                # Extract text content from markdown
                with open(file_path, 'r', encoding='utf-8') as f:
                    content = f.read()
                
                # Remove markdown formatting for spell check
                text_content = self.extract_text_for_spell_check(content)
                
                # Write temporary file for aspell
                temp_file = file_path.parent / f".{file_path.name}.spellcheck"
                with open(temp_file, 'w', encoding='utf-8') as f:
                    f.write(text_content)
                
                # Run aspell
                cmd = ['aspell', 'list', '--mode=none', '--personal=/dev/null']
                result = subprocess.run(cmd, input=text_content, capture_output=True, text=True, encoding='utf-8')
                
                if result.stdout:
                    errors = result.stdout.strip().split('\n')
                    # Filter out technical terms
                    filtered_errors = [word for word in errors if word.lower() not in self.technical_words]
                    
                    if filtered_errors:
                        total_errors += len(filtered_errors)
                        self.results['spell_check']['errors'].append({
                            'file': str(file_path),
                            'words': filtered_errors,
                            'count': len(filtered_errors)
                        })
                
                # Clean up temp file
                if temp_file.exists():
                    temp_file.unlink()
                    
            except Exception as e:
                print(f"   ⚠️  Error spell-checking {file_path}: {str(e)}")
        
        print(f"   🔤 Total potential spelling errors: {total_errors}")
        return total_errors == 0

    def extract_text_for_spell_check(self, markdown_content):
        """Extract plain text from markdown for spell checking."""
        # Remove code blocks
        content = re.sub(r'```.*?```', '', markdown_content, flags=re.DOTALL)
        content = re.sub(r'`[^`]+`', '', content)
        
        # Remove links but keep text
        content = re.sub(r'\[([^\]]+)\]\([^)]+\)', r'\1', content)
        
        # Remove image references
        content = re.sub(r'!\[[^\]]*\]\([^)]+\)', '', content)
        
        # Remove HTML tags
        content = re.sub(r'<[^>]+>', '', content)
        
        # Remove markdown formatting
        content = re.sub(r'[*_#]+', '', content)
        
        # Remove URLs
        content = re.sub(r'https?://[^\s]+', '', content)
        
        return content

    def generate_report(self, output_file=None):
        """Generate comprehensive quality report."""
        if output_file is None:
            output_file = self.root_dir / 'docs_quality_report.json'
        
        # Calculate summary statistics
        summary = {
            'scan_timestamp': self.results['metadata']['scan_time'],
            'total_files_scanned': self.results['metadata']['total_files'],
            'total_links_checked': self.results['metadata']['total_links'],
            'total_images_checked': self.results['metadata']['total_images'],
            'link_success_rate': 0,
            'image_success_rate': 0,
            'format_compliance_rate': 0,
            'overall_score': 0
        }
        
        # Calculate success rates
        total_links = len(self.results['link_check']['passed']) + len(self.results['link_check']['failed'])
        if total_links > 0:
            summary['link_success_rate'] = len(self.results['link_check']['passed']) / total_links * 100
        
        total_images = len(self.results['image_check']['passed']) + len(self.results['image_check']['failed'])
        if total_images > 0:
            summary['image_success_rate'] = len(self.results['image_check']['passed']) / total_images * 100
        
        total_format = len(self.results['format_check']['passed']) + len(self.results['format_check']['failed'])
        if total_format > 0:
            summary['format_compliance_rate'] = len(self.results['format_check']['passed']) / total_format * 100
        
        # Calculate overall score
        scores = [
            summary['link_success_rate'],
            summary['image_success_rate'],
            summary['format_compliance_rate']
        ]
        summary['overall_score'] = sum(scores) / len(scores) if scores else 0
        
        # Create final report
        report = {
            'summary': summary,
            'detailed_results': self.results,
            'recommendations': self.generate_recommendations()
        }
        
        # Write report
        with open(output_file, 'w', encoding='utf-8') as f:
            json.dump(report, f, indent=2, ensure_ascii=False)
        
        print(f"\n📊 Quality Report Generated: {output_file}")
        print(f"   Overall Score: {summary['overall_score']:.1f}%")
        print(f"   Link Success Rate: {summary['link_success_rate']:.1f}%")
        print(f"   Image Success Rate: {summary['image_success_rate']:.1f}%")
        print(f"   Format Compliance: {summary['format_compliance_rate']:.1f}%")
        
        return report

    def generate_recommendations(self):
        """Generate actionable recommendations based on test results."""
        recommendations = []
        
        # Link recommendations
        failed_links = len(self.results['link_check']['failed'])
        if failed_links > 0:
            recommendations.append({
                'category': 'Links',
                'priority': 'High' if failed_links > 10 else 'Medium',
                'issue': f"{failed_links} broken links found",
                'action': "Fix broken internal links and verify external URLs"
            })
        
        # Image recommendations
        failed_images = len(self.results['image_check']['failed'])
        missing_alt = len(self.results['image_check']['warnings'])
        if failed_images > 0:
            recommendations.append({
                'category': 'Images',
                'priority': 'High',
                'issue': f"{failed_images} missing images found",
                'action': "Add missing image files or fix image paths"
            })
        
        if missing_alt > 0:
            recommendations.append({
                'category': 'Accessibility',
                'priority': 'Medium',
                'issue': f"{missing_alt} images missing alt text",
                'action': "Add descriptive alt text for all images"
            })
        
        # Format recommendations
        format_issues = len(self.results['format_check']['failed'])
        if format_issues > 0:
            recommendations.append({
                'category': 'Format',
                'priority': 'Low',
                'issue': f"{format_issues} files with formatting issues",
                'action': "Fix markdown formatting and structure issues"
            })
        
        # Structure recommendations
        structure_issues = len(self.results['structure_check']['failed'])
        if structure_issues > 0:
            recommendations.append({
                'category': 'Structure',
                'priority': 'High',
                'issue': "Documentation structure incomplete",
                'action': "Create missing required files and directories"
            })
        
        return recommendations

    def fix_common_issues(self, dry_run=True):
        """Attempt to fix common documentation issues automatically."""
        print(f"🔧 {'Analyzing' if dry_run else 'Fixing'} common issues...")
        
        fixes_applied = []
        
        # Fix relative links that could be corrected
        for failed_link in self.results['link_check']['failed']:
            if failed_link['type'] == 'internal':
                # Try to find the file with a similar name
                link_path = failed_link['link']
                file_path = Path(failed_link['file'])
                
                # Simple fixes for common issues
                if link_path.endswith('.md') and not link_path.startswith(('http', 'mailto')):
                    # Search for files with similar names
                    search_name = Path(link_path).name.lower()
                    
                    for md_file in self.find_markdown_files():
                        if md_file.name.lower() == search_name:
                            # Calculate relative path
                            try:
                                rel_path = os.path.relpath(md_file, file_path.parent)
                                fix = {
                                    'file': str(file_path),
                                    'original_link': link_path,
                                    'suggested_fix': rel_path,
                                    'type': 'relative_path_correction'
                                }
                                fixes_applied.append(fix)
                                
                                if not dry_run:
                                    # Apply the fix
                                    with open(file_path, 'r', encoding='utf-8') as f:
                                        content = f.read()
                                    
                                    # Replace the link
                                    old_pattern = f']({re.escape(link_path)})'
                                    new_pattern = f']({rel_path})'
                                    new_content = re.sub(old_pattern, new_pattern, content)
                                    
                                    with open(file_path, 'w', encoding='utf-8') as f:
                                        f.write(new_content)
                                
                                break
                            except (ValueError, OSError):
                                continue
        
        print(f"   🔧 {len(fixes_applied)} potential fixes identified")
        
        if dry_run:
            print("   ℹ️  Run with --fix-links to apply fixes automatically")
        else:
            print(f"   ✅ {len(fixes_applied)} fixes applied")
        
        return fixes_applied

def main():
    parser = argparse.ArgumentParser(
        description="Documentation Quality Assurance Suite",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Examples:
  python docs_qa_suite.py --full-audit
  python docs_qa_suite.py --check-links --validate-format
  python docs_qa_suite.py --fix-links --generate-report
        """
    )
    
    parser.add_argument('--check-links', action='store_true', help='Check all links')
    parser.add_argument('--validate-format', action='store_true', help='Validate markdown format')
    parser.add_argument('--check-images', action='store_true', help='Check image references')
    parser.add_argument('--spell-check', action='store_true', help='Run spell checking')
    parser.add_argument('--full-audit', action='store_true', help='Run complete audit')
    parser.add_argument('--fix-links', action='store_true', help='Attempt to fix broken links')
    parser.add_argument('--generate-report', action='store_true', help='Generate quality report')
    parser.add_argument('--no-external', action='store_true', help='Skip external link checking')
    parser.add_argument('--root-dir', default='.', help='Root directory to scan')
    
    args = parser.parse_args()
    
    # If no specific checks requested, run basic checks
    if not any([args.check_links, args.validate_format, args.check_images, 
                args.spell_check, args.full_audit]):
        args.check_links = True
        args.validate_format = True
        args.check_images = True
    
    # Initialize QA suite
    qa = DocumentationQA(args.root_dir)
    
    print("📚 Documentation Quality Assurance Suite")
    print("=" * 50)
    
    overall_success = True
    
    # Run selected checks
    if args.full_audit or args.check_links:
        success = qa.check_links(check_external=not args.no_external)
        overall_success = overall_success and success
    
    if args.full_audit or args.validate_format:
        success = qa.check_markdown_format()
        overall_success = overall_success and success
    
    if args.full_audit or args.check_images:
        success = qa.check_images()
        overall_success = overall_success and success
    
    if args.full_audit:
        success = qa.check_documentation_structure()
        overall_success = overall_success and success
    
    if args.full_audit or args.spell_check:
        qa.run_spell_check()  # Don't fail on spell check issues
    
    # Fix links if requested
    if args.fix_links:
        qa.fix_common_issues(dry_run=False)
    
    # Generate report
    if args.full_audit or args.generate_report:
        qa.generate_report()
    
    print("\n" + "=" * 50)
    if overall_success:
        print("🎉 All documentation quality checks passed!")
        sys.exit(0)
    else:
        print("⚠️  Some documentation quality issues found.")
        print("   Run with --generate-report for detailed analysis.")
        sys.exit(1)

if __name__ == '__main__':
    main()