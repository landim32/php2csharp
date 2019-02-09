using System;
using System.Collections.Generic;
using System.Diagnostics;
using System.IO;
using System.Linq;
using System.Reflection;
using System.Text;
using System.Threading.Tasks;

namespace PHP2CSharp.Core
{
    public class PHP2CSharpConsole
    {
        private PHP2CSharpConverter _php2csharp = new PHP2CSharpConverter();
        public string OriginDir { get; set; }
        public string DestinyDir { get; set; }

        private void executeDir(string dir) {
            string relativeDir = dir.Substring(OriginDir.Length);
            string destinyDir = DestinyDir;
            if (!string.IsNullOrEmpty(relativeDir)) {
                relativeDir = relativeDir.Substring(1);
                destinyDir = Path.Combine(DestinyDir, relativeDir);
            }
            if (!Directory.Exists(destinyDir))
            {
                Directory.CreateDirectory(destinyDir);
                Console.WriteLine(".\\" + relativeDir + " (created!)");
            }
            else {
                Console.WriteLine(".\\" + relativeDir + " (exist)");
            }
            string fullPathDir = Path.Combine(OriginDir, dir);
            foreach (var dirPath in Directory.GetDirectories(fullPathDir)) {
                executeDir(dirPath);
            }
            foreach (var file in Directory.GetFiles(fullPathDir)) {
                if (file.EndsWith(".php", true, null))
                {
                    string relativeOrigPath = file.Substring(OriginDir.Length + 1);
                    string relativeDestPath = relativeOrigPath.Substring(0, relativeOrigPath.Length - 3) + "cs";
                    string destinyPath = Path.Combine(DestinyDir, relativeDestPath);

                    if (!File.Exists(destinyPath))
                    {

                        string origSource = File.ReadAllText(file);
                        var destinySource = _php2csharp.convert(origSource);
                        File.WriteAllText(destinyPath, destinySource);

                        Console.WriteLine(".\\" + relativeOrigPath + " -> " + relativeDestPath + " (ok)");
                    }
                    else {
                        Console.WriteLine(".\\" + relativeOrigPath + " -> " + relativeDestPath + " (exist!)");
                    }
                }
            }
        }

        private string getVersion() {
            Assembly assembly = Assembly.GetExecutingAssembly();
            FileVersionInfo fileVersionInfo = FileVersionInfo.GetVersionInfo(assembly.Location);
            return fileVersionInfo.ProductVersion;
        }

        public bool execute(string[] args)
        {
            Console.WriteLine(string.Format("@PHP2CSharp {0} by Rodrigo Landim", getVersion()));

            if (args.Length >= 1) {
                OriginDir = args[0];
            }

            if (args.Length >= 2)
            {
                DestinyDir = args[1];
            }

            if (string.IsNullOrEmpty(OriginDir)) {
                Console.WriteLine(string.Format("ERROR: Origin is empty!", OriginDir));
                return false;
            }

            if (string.IsNullOrEmpty(DestinyDir))
            {
                Console.WriteLine(string.Format("ERROR: Destiny is empty!", OriginDir));
                return false;
            }

            if (!Directory.Exists(OriginDir))
            {
                Console.WriteLine(string.Format("ERROR: Origin directory '{0}' not exists!", OriginDir));
                return false;
            }

            if (!Directory.Exists(DestinyDir))
            {
                Console.WriteLine(string.Format("ERROR: Destiny directory '{0}' not exists!", DestinyDir));
                return false;
            }

            executeDir(OriginDir);
            return true;
        }
    }
}
